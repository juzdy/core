<?php
namespace Juzdy\EventBus\Event;

use Juzdy\EventBus\Event\ContextInterface;

class Event implements EventInterface
{
    
    protected bool $propagationStopped = false;

    /**
     * @param array $properties Initial properties for the event
     */
    public function __construct(protected ContextInterface $context)
    {
    }

    /**
     * Clone magic method to ensure deep copy of context.
     */
    public function __clone()
    {
        $this->context = clone $this->context;
    }

    /**
     * Get the context instance.
     *
     * @return ContextInterface The context instance
     */
    protected function getContext(): ContextInterface
    {
        return $this->context;
    }

    /**
     * {@inheritDoc}
     */
    public function attach(string $name, mixed $value): static
    {
        $this->getContext()->set($name, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function with(string $name, mixed $value): static
    {
        $new = clone $this;
        $new->attach($name, $value);

        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $name): mixed
    {
        return $this->getContext()->get($name);
    }

    /**
     * {@inheritDoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * {@inheritDoc}
     */
    public function stopPropagation(): static
    {
        $this->propagationStopped = true;
        return $this;
    }
}