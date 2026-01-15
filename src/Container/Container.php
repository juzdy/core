<?php

namespace Juzdy\Container;

use Juzdy\Container\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use Juzdy\Container\Context\Context;
use Juzdy\Container\Context\ContextInterface;
use Juzdy\Container\Exception\CircularDependencyException;
use Juzdy\Container\Plugin\PluginInterface;
use Juzdy\Container\Plugin\Resolver\AttributeClass;
use Juzdy\Container\Plugin\Resolver\AttributeParam;
use Juzdy\Container\Plugin\Resolver\InterfaceResolver;
use Juzdy\Container\Plugin\Resolver\NotFound;
use Juzdy\Container\Plugin\Resolver\TypeResolver;
use Juzdy\Container\Plugin\Factory\FallbackFactory;
use Juzdy\Container\Plugin\Factory\LazyGhostFactory;
use Juzdy\Container\Plugin\Factory\StandardFactory;
use Juzdy\Container\Plugin\LifeCycle\Prototype;
use Juzdy\Container\Plugin\LifeCycle\Shared;
use Juzdy\Container\Plugin\Resolver\AttributeClassPropagated;
use Throwable;

/**
 * Simple Dependency Injection Container
 *
 * @package Juzdy\Container
 */
class Container implements JuzdyContainerInterface
{
    
    /**
     * @var array<string, mixed> Registered services
     */
    protected array $services = [];

    /**
     * @var array<int, string> Stack of currently resolving services
     */
    protected array $stack = [];

    /**
     * @var array<string, bool> Currently resolving services
     */
    protected array $resolving = [];

    /**
     * @var array<int, array<string, string>> Runtime preferences for interfaces
     */
    protected array $propagatedPreferences = [];
    
    /**
     * @var PluginManagerInterface|null The resolve plugin manager.
     */
    protected ?PluginManagerInterface $resolveManager = null;

    /**
     * @var PluginManagerInterface|null The factory plugin manager
     */
    protected ?PluginManagerInterface $factoryManager = null;

    /**
     * @var PluginManagerInterface|null The aware plugin manager.
     */
    protected ?PluginManagerInterface $awareManager = null;

    /**
     * @var PluginManagerInterface|null The lifecycle plugin manager.
     * 
     */
    protected ?PluginManagerInterface $lifecycleManager = null;

    /**
     * @var PluginManagerInterface|null The lifecycle plugin manager.
     * 
     */
    protected ?PluginManagerInterface $requireManager = null;

    /**
     * Container constructor.
     * Initializes the container and registers default plugins.
     */
    public function __construct()
    {
        $this->initPlugins();
    }

    /**
     * Initialize and register default plugins
     *
     * @return static
     */
    protected function initPlugins(): static
    {
        $this->getRequireManager(
            new Prototype(),          //First registered, last executed
        );

        $this->getResolveManager(
            new NotFound(),                 //First registered, last executed
            new InterfaceResolver(),
            new TypeResolver(),
            new AttributeClass(),
            //new AttributeClassPropagated(),
            new AttributeParam(),
        );

        $this->getFactoryManager(
            new FallbackFactory(),          //First registered, last executed
            new StandardFactory(),
            new LazyGhostFactory(),
            //new CustomFactory(),          //Last registered, first executed
        );

        $this->getAwareManager(
            new Plugin\Aware\Injector()
        );

        $this->getLifecycleManager(
            new Shared()
        );        
        return $this;
    }

    public function propagatePreference(string $id, string $preference): static
    {
        array_unshift(
            $this->propagatedPreferences[$id] ??= [],
            $preference
        );

        return $this;
    }

    public function getPropagatedPreferences(): array
    {
        return $this->propagatedPreferences;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        return 
            $id === ContainerInterface::class 
            || $this->hasLocal($id)
            || $this->can($id)
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function can(string $id): bool
    {
        try {
            $context = new Context($id, $this);

            return $context->reflection()->isInstantiable();

        } catch (Throwable) {
            
        }

        return false;
    }

    protected function hasLocal(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }

    
    protected function getLocal(string $id): mixed
    {
        return $this->services[$id];
    }

    protected function forget(string $id): static
    {
        unset($this->services[$id]);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $id): mixed
    {
        if (isset($this->resolving[$id])) {
            throw new CircularDependencyException('Circular dependency detected while resolving service ' . $id . '. Stack: ' . implode(' -> ', $this->stack) . ' -> ' . $id);
        }

        array_push($this->stack, $id);
        $this->resolving[$id] = true;

        try {
            $service = match (true) {
                $id === ContainerInterface::class => $this,
                $this->hasLocal($id) => $this->require($id),
                default => $this->create($id),
            };
        } finally {
            array_pop($this->stack);
            unset($this->resolving[$id]);
        }

        return $service;
    }
    
    
    protected function require(string $id): mixed
    {
        $context = new Context($id, $this);
        $context->instance($this->getLocal($id));
        
        $this->getRequireManager()
                ->process($context);

        return $context->instance();
    }
    /**
     * {@inheritDoc}
     */
    public function share(string $id, mixed $instance): static
    {
        $this->services[$id] = $instance;

        return $this;
    }

    /**
     * Create the service instance for the given identifier.
     * Pipelines through resolver, factory, and aware plugins.
     *
     * @param string $id The service identifier
     * 
     * @return mixed The created service instance
     */
    protected function create(string $id): mixed
    {
        $context = new Context($id, $this);

        if (!$context->reflection()->isInstantiable()) {
            throw new NotFoundException('Service ' . $id . ' is not instantiable.');
        }

        return $this
            ->resolve($context)
            ->factory($context)
            ->aware($context)
            ->lifecycle($context)
            ->instance($context);
    }

    /**
     * Get the instance from the context.
     *
     * @param ContextInterface $context The context to get the instance from
     * 
     * @return mixed The instance from the context
     */
    protected function instance(ContextInterface $context): mixed
    {
        return $context->instance();
    }

    /**
     * Resolve dependencies for the context.
     *
     * @param ContextInterface $context The context to resolve dependencies for
     * 
     * @return static
     */
    private function resolve(ContextInterface $context): static
    {
        foreach ($context->constructor()?->getParameters() ?? [] as $param) {
            //try {
                $dep = $this->getResolveManager()
                    ->process($context->attribute(ContextInterface::ATTRIBUTE_CURRENT_PARAMETER, $param));
            // } catch (Throwable $ex) {
            //     $class = $context->reflection()->getName();
            //     $pname = $param->getName();
            //     $ptype = $param->getType()?->__toString() ?? 'mixed';
            //     throw new NotFoundException(
            //         "Cannot resolve {$class}::__construct(...{$ptype} \${$pname}...); Reason: " . $ex->getMessage(),
            //         0,
            //         $ex
            //     );
            // }

            $context->depends($dep);
        }


        return $this;
    }

    /**
     * Process factory plugins for the context.
     *
     * @param ContextInterface $context The context to process factory plugins for
     * 
     * @return static
     */
    protected function factory(ContextInterface $context): static
    {
        $context->instance(
            $this->getFactoryManager()
                ->process($context)
        );

        return $this;
    }

    /**
     * Process aware plugins for the context.
     *
     * @param ContextInterface $context The context to process aware plugins for
     * 
     * @return static
     */
    private function aware(ContextInterface $context): static
    {
        $this->getAwareManager()
                ->process($context);

        return $this;
    }

    /**
     * Process lifecycle plugins for the context.
     *
     * @param ContextInterface $context The context to process lifecycle plugins for
     * 
     * @return static
     */
    private function lifecycle(ContextInterface $context): static
    {
        $this->getLifecycleManager()
                ->process($context);

        return $this;
    }

    /**
     * Get or create the resolve plugin manager.
     * 
     * @param PluginInterface ...$plugins Plugins to register
     *
     * @return PluginManagerInterface The resolve plugin manager
     */
    protected function getResolveManager(PluginInterface ...$plugins): PluginManagerInterface
    {
        if ($this->resolveManager === null) {
            $this->resolveManager = new PluginManager(...$plugins);
        }

        foreach ($plugins as $plugin) {
            $this->resolveManager->register($plugin);
        }

        return $this->resolveManager;
    }

    /**
     * Get or create the factory plugin manager.
     * 
     * @param PluginInterface ...$plugins Plugins to register
     *
     * @return PluginManagerInterface The factory plugin manager
     */
    protected function getFactoryManager(PluginInterface ...$plugins): PluginManagerInterface
    {
        if ($this->factoryManager === null) {
            $this->factoryManager = new PluginManager(...$plugins);
        }

        foreach ($plugins as $plugin) {
            $this->factoryManager->register($plugin);
        }

        return $this->factoryManager;
    }

    /**
     * Get or create the aware plugin manager.
     * 
     * @param PluginInterface ...$plugins Plugins to register
     *
     * @return PluginManagerInterface The aware plugin manager
     */
    protected function getAwareManager(PluginInterface ...$plugins): PluginManagerInterface
    {
        if ($this->awareManager === null) {
            $this->awareManager = new PluginManager(...$plugins);
        }

        foreach ($plugins as $plugin) {
            $this->awareManager->register($plugin);
        }

        return $this->awareManager;
    }


    protected function getLifecycleManager(PluginInterface ...$plugins): PluginManagerInterface
    {
        if ($this->lifecycleManager === null) {
            $this->lifecycleManager = new PluginManager(...$plugins);
        }

        foreach ($plugins as $plugin) {
            $this->lifecycleManager->register($plugin);
        }

        return $this->lifecycleManager;
    }

    protected function getRequireManager(PluginInterface ...$plugins): PluginManagerInterface
    {
        
        if ($this->requireManager === null) {
            $this->requireManager = new PluginManager(...$plugins);
        }

        foreach ($plugins as $plugin) {
            $this->requireManager->register($plugin);
        }

        return $this->requireManager;
    }

}