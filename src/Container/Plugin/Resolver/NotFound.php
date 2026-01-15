<?php

namespace Juzdy\Container\Plugin\Resolver;

use Juzdy\Container\Context\ContextInterface;
use Juzdy\Container\Exception\FinalNotFoundException;
use Juzdy\Container\Plugin\PluginInterface;
use ReflectionParameter;

class NotFound implements PluginInterface
{

    public function __invoke(mixed $target, callable $next): mixed
    {
        /** @var ContextInterface $context */
        /** @var \ReflectionParameter $param */
        $context = $target;
        $param = $context->attribute(ContextInterface::ATTRIBUTE_CURRENT_PARAMETER);

        throw new FinalNotFoundException ("[{$context->class()}]: Cannot resolve parameter '{$param->getName()}' with type '{$param->getType()}'.");
    }
}
