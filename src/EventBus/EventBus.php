<?php
namespace Juzdy\EventBus;

class EventBus implements EventBusInterface
{

    private function __construct(
        protected ListenerProviderInterface $listenerProvider,
        protected EventDispatcherInterface $dispatcher
    )
    {}


    /**
     * {@inheritDoc}
     */
    public function dispatch(EventInterface $event): EventInterface
    {
        return $this->getEventDispatcher()->dispatch($event);
    }

    /**
     * {@inheritDoc}
     */
    public function listen(string|EventInterface $event, callable $listener, int $priority = 0): static
    {
        $this->getListenerProvider()->addListener($event, $listener, $priority);

        return $this;
    }

    /**
     * Get the listener provider instance.
     *
     * @return ListenerProviderInterface The listener provider instance
     */
    protected function getListenerProvider(): ListenerProviderInterface
    {
        return $this->listenerProvider;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return EventDispatcherInterface The event dispatcher instance
     */
    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }
}