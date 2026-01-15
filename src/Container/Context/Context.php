<?php
namespace Juzdy\Container\Context;

use Juzdy\Container\JuzdyContainerInterface;
use ReflectionClass;
use ReflectionMethod;

class Context implements ContextInterface
{
    /** @var mixed */
    protected mixed $instance = null;

    protected ?string $className = null;
    
    /** @var ReflectionClass|null */
    protected ?ReflectionClass $reflectionClass = null;

    /** @var array<int, mixed> */
    protected array $dependencies = [];

    /** @var array<string, mixed> */
    protected array $attributes = [];


    /** 
     * @param string $className
     * @param JuzdyContainerInterface $container
     */
    public function __construct(
        private string $id, 
        private JuzdyContainerInterface $container
        )
    {        
        $this->className = $id;
    }

    /**
     * Get the container associated with the context
     *
     * @return JuzdyContainerInterface
     */
    public function getContainer(): JuzdyContainerInterface
    {
        return $this->container;
    }

    /**
     * {@inheritDoc}
     */
    public function instance(mixed $instance = null): mixed
    {
        if ($instance !== null) {
            $this->instance = $instance;

            return $this;
        }

        return $this->instance;
    }

    /**
     * {@inheritDoc}
     */
    public function attribute(string $name, mixed $value = null): mixed
    {
        if ($value !== null) {
            $this->attributes[$name] = $value;

            return $this;
        }

        return $this->attributes[$name] ?? null;
    }

    /**
     * Get the identifier of the service associated with the context
     * 
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get the class name associated with the context
     * 
     * @return string
     */
    public function class(): string
    {
        return $this->className;
    }

    /**
     * Get the ReflectionClass instance for the context's class
     * @return ReflectionClass
     */
    public function reflection(): ReflectionClass
    {
        return $this->reflectionClass ??= new ReflectionClass($this->class());
    }

    /**
     * Get the constructor method of the class
     *
     * @return ReflectionMethod|null
     */
    public function constructor(): ?ReflectionMethod
    {
        return $this->reflection()->getConstructor();
    }

    /**
     * Check if the class has a constructor
     *
     * @return bool
     */
    public function hasConstructor(): bool
    {
        return $this->constructor() !== null;
    }

    /**
     * Register dependencies for the context and return the list of all dependencies
     *
     * @param mixed ...$dependencies    Variadic list of dependencies to register
     * 
     * @return array                    The list of all registered dependencies
     */
    public function depends(...$dependencies): array
    {
        foreach ($dependencies as $dependency) {
            $this->dependencies[] = $dependency;
        }

        return $this->dependencies;
    }

}