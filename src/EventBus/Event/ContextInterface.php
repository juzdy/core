<?php
namespace Juzdy\EventBus\Event;

interface ContextInterface
{
    /**
     * Gets a property value from the context.
     *
     * @param string $id The property name
     * @return mixed The property value
     */
    public function get(string $id): mixed;

    /**
     * Checks if the context has a property.
     *
     * @param string $id The property name
     * @return bool True if the property exists, false otherwise
     */
    public function has(string $id): bool;

    /**
     * Sets a property on the context and returns the context instance.
     *
     * @param string $id The property name
     * @param mixed $value The property value
     * @return ContextInterface The context instance
     */
    public function set(string $id, mixed $value): ContextInterface;

    /**
     * Sets a property on the context and returns the NEW context instance.
     *
     * @param string $id The property name
     * @param mixed $value The property value
     * @return ContextInterface The context instance
     */
    public function with(string $id, mixed $value): ContextInterface;

}