<?php
namespace Juzdy\Container\Plugin\Factory;

use Juzdy\Container\Contract\LazyGhostInterface;
use Juzdy\Container\Plugin\PluginInterface;

/**
 * Lazy ghost factory plugin
 * Instantiates classes implementing LazyGhostInterface as lazy ghost proxies
 *
 * @package Juzdy\Container
 */
class LazyGhostFactory implements PluginInterface
{

    /**
     * {@inheritDoc}
     * 
     * Instantiates classes implementing LazyGhostInterface as lazy ghost proxies
     * using the provided dependencies when the proxy is initialized.
     */
    public function __invoke(mixed $context, callable $next): mixed
    {
        $reflection = $context->reflection();

        $hasAccessableConstructor =
            $reflection->getConstructor() &&
            $reflection->getConstructor()->isPublic();

        if (
            is_a($context->class(), LazyGhostInterface::class, true)
            && $hasAccessableConstructor
        ) {
            
            
            return $reflection->newLazyGhost(
                static function ($object) use ($context, $hasAccessableConstructor) {
                    if ($hasAccessableConstructor) {
                        $object->__construct(...$context->depends());
                    }
                }
            );
        }

        return $next($context);
    }
}