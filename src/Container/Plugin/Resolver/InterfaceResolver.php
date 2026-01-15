<?php

namespace Juzdy\Container\Plugin\Resolver;

use Juzdy\Container\Context\ContextInterface;
use Juzdy\Container\Plugin\PluginInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionParameter;
use RuntimeException;

/**
 * Interface resolver plugin
 * Resolves interface type-hinted parameters by convention-based resolution:
 * if an interface is type-hinted, it attempts to resolve a class with the same name minus the 'Interface' suffix.
 * 
 *
 * @package Juzdy\Container\Plugin\Resolver
 */
class InterfaceResolver extends AbstractResolverPlugin implements PluginInterface
{

    public function __invoke(mixed $target, callable $next): mixed
    {
        /** @var ContextInterface $context */
        /** @var \ReflectionParameter $param */
        $context = $target;
        $param = $context->attribute(ContextInterface::ATTRIBUTE_CURRENT_PARAMETER);

        $type = $this->paramType($param);

        $id = $type->getName();

        if (str_ends_with($id, 'Interface')) {
            if (interface_exists($id) ) {
                // Auto-resolve interface to class by removing 'Interface' suffix
                // Simple convention-based resolution
                // this allowed only for top-level service ids, dependencies will be resolved normally
                $id = preg_replace('/Interface$/', '', $id);

                if (class_exists($id)) {
                        return $context->getContainer()->get($id);
                }
            }
        }

        return $next($target);
    }
}
