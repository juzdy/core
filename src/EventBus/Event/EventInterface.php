<?php
namespace Juzdy\EventBus\Event;

use Psr\EventDispatcher\StoppableEventInterface;

interface EventInterface extends StoppableEventInterface
{
    /**
     * Attach a property on the event and returns the event instance.
     *
     * @param string $name The property name
     * @param mixed $value The property value
     * @return static The event instance
     */
    public function attach(string $name, mixed $value): static;

    /**
     * Sets a property on the event and returns the NEW event instance.
     *
     * @param string $name The property name
     * @param mixed $value The property value
     * @return static The event instance
     */
    public function with(string $name, mixed $value): static;

    /**
     * Gets a property value from the event.
     *
     * @param string $name The property name
     * @return mixed The property value
     */
    public function get(string $name): mixed;

    /**
     * Checks if the event propagation has been stopped.
     *
     * @return bool True if propagation is stopped, false otherwise
     */
    public function isPropagationStopped(): bool;

    /**
     * Stops the event propagation.
     *
     * @return static The event instance
     */
    public function stopPropagation(): static;
}