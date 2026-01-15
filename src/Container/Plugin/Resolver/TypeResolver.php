<?php

namespace Juzdy\Container\Plugin\Resolver;

use ReflectionParameter;
use Juzdy\Container\Context\ContextInterface;
use Juzdy\Container\Exception\RuntimeException;
use Juzdy\Container\Plugin\PluginInterface;
use Psr\Container\NotFoundExceptionInterface;

class TypeResolver extends AbstractResolverPlugin implements PluginInterface
{

    public function __invoke(mixed $target, callable $next): mixed
    {
        /** @var ContextInterface $context */
        /** @var \ReflectionParameter $param */
        $context = $target;
        $param = $context->attribute(ContextInterface::ATTRIBUTE_CURRENT_PARAMETER);

       $type = $this->paramType($param);

       //todo handle union and intersection types in future

        if ($type->isBuiltin()) {

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            throw new RuntimeException (
                sprintf(
                    "Cannot resolve built-in type parameter '%s' in class '%s'.",
                    $param->getName(),
                    $context->class(),
                )
            );
        }

        $typeName = $type->getName();
        $preference = $typeName;

        try {
            $service = $context->getContainer()->get($preference);
            
            return $service;
        } catch (NotFoundExceptionInterface) {
            // Service not found, continue to next plugin
        }

        return $next($target);
    }
}
