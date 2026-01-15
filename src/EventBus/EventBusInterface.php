<?php
namespace Juzdy\EventBus;

interface EventBusInterface //extends \Psr\EventDispatcher\EventDispatcherInterface
{
    /**
     * Dispatches an event to all registered listeners.
     *
     * @param EventInterface $event The event instance to dispatch
     * 
     * @return EventInterface The dispatched event instance
     */
    public function dispatch(EventInterface $event): EventInterface;

    /**
     * Registers a listener for a specific event with an optional priority.
     *
     * @param string|EventInterface $event The event class name or instance
     * @param callable $listener The listener callable
     * @param int $priority The priority of the listener (higher values indicate higher priority)
     * 
     * @return static The event bus instance
     */
    public function listen(string|EventInterface $event, callable $listener, int $priority = 0): static;
}