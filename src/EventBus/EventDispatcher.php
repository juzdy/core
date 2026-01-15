<?php
namespace Juzdy\EventBus;

class EventDispatcher implements EventDispatcherInterface
{
    /** @var ListenerProviderInterface */
    public function __construct(protected ListenerProviderInterface $listenerProvider)
    {
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
     * {@inheritDoc}
     */
    public function dispatch(object $event): static
    {
        foreach ($this->getListenerProvider()->getListenersForEvent($event) as $listener) {
            $listener($event);

            if ($event->isPropagationStopped()) {
                break;
            }
        }

        return $this;
    }
}