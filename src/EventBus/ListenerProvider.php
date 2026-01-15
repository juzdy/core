<?php
namespace Juzdy\EventBus;

use Juzdy\EventBus\Event\EventInterface;
use Traversable;

class ListenerProvider implements ListenerProviderInterface
{

    /** @var array<string, array<int, array<callable>>> */
    protected  array $listeners = [];
    
    /**
     * {@inheritDoc}
     */
    public function addListener(string|EventInterface $event, callable $listener, int $priority = 0): static
    {
        $eventId = is_string($event) ? $event : $event::class;
        $this->listeners[$eventId][$priority][] = $listener;
        
        krsort($this->listeners[$eventId]);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getListenersForEvent(string|EventInterface $event): Traversable
    {
        $eventIds = is_string($event) ? [$event] : array_merge([$event::class], class_parents($event), class_implements($event));
        foreach ($eventIds as $eventId) {
            if (isset($this->listeners[$eventId])) {
                foreach ($this->listeners[$eventId] as $priorityListeners) {
                    foreach ($priorityListeners as $listener) {
                        yield $listener;
                    }
                }
            }
        }
    }
    
}