<?php
namespace Juzdy\EventBus\Event;

class Context implements ContextInterface
{

    /** @var array<string, mixed> */
    private array $properties = [];

    /**
     * Clone magic method to ensure deep copy of properties array.
     */
    public function __clone()
    {
        $this->properties = array_merge([], $this->properties);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $id): mixed
    {
        return $this->properties[$id] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->properties);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $id, mixed $value): ContextInterface
    {
        $this->properties[$id] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function with(string $id, mixed $value): ContextInterface
    {
        $newContext = clone $this;
        $newContext->set($id, $value);

        return $newContext;
    }
}