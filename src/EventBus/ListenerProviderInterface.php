<?php
namespace Juzdy\EventBus;

use Juzdy\EventBus\Event\EventInterface;
use Traversable;

interface ListenerProviderInterface //extends \Psr\EventDispatcher\ListenerProviderInterface
{   
    /**
     * Register a listener for a specific event with an optional priority.
     *
     * @param string|EventInterface $event The event class name or instance
     * @param callable $listener The listener callable
     * @param int $priority The priority of the listener (higher values indicate higher priority)
     * 
     * @return static The listener provider instance
     */
    public function addListener(string|EventInterface $event, callable $listener, int $priority = 0): static;

    /**
     * Get all listeners for a specific event, ordered by priority.
     *
     * @param EventInterface $event The event instance
     * 
     * @return Traversable<callable> A traversable list of listener callables
     */
    public function getListenersForEvent(EventInterface $event): Traversable;
    
}