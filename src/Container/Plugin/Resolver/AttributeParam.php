<?php
namespace Juzdy\Container\Plugin\Resolver;

use Juzdy\Container\Attribute\Parameter\Using;
use Juzdy\Container\Context\ContextInterface;
use Juzdy\Container\Exception\RuntimeException;
use Juzdy\Container\Plugin\PluginInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionParameter;

/**
 * Parameter attribute resolver plugin
 *
 * @package Juzdy\Container\Plugin\Resolver
 */
class AttributeParam extends AbstractResolverPlugin implements PluginInterface
{

    /**
     * {@inheritDoc}
     *
     * Resolves parameter preferences defined via Using attribute on the target parameter.
     */
    public function __invoke(mixed $target, callable $next): mixed
    {
        /** @var ContextInterface $context */
        /** @var \ReflectionParameter $param */
        $context = $target;
        $param = $context->attribute(ContextInterface::ATTRIBUTE_CURRENT_PARAMETER);

        $attributes = $param->getAttributes(Using::class);
        if (count($attributes) > 0) {
            $attribute = $attributes[0];

            /** @var Using $parameterInstance */
            $parameterInstance = $attribute->newInstance();
            $preference = $parameterInstance->getPreference();

            if ($preference !== null) {
                $type = $this->paramType($param);
                if (!is_a($preference, $type->getName(), true)) {
                    throw new RuntimeException("Preference '{$preference}' is not a valid implementation of '{$type->getName()}'.");
                }
                
                return $context->getContainer()->get($preference);
            }
        }

        return $next($target);
    }
}