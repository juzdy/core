<?php
namespace Juzdy;

use Juzdy\Composer\Composer;
use Psr\EventDispatcher\EventDispatcherInterface;
use Juzdy\Container\Attribute\Parameter\Using;
use Juzdy\Container\Attribute\Preference;
use Juzdy\Container\Attribute\PropagatePreference;
use Juzdy\EventBus\Event\EventInterface;
use Psr\Container\ContainerInterface;

/**
 * Application bootstrapper
 *
 * @package Juzdy
 */

/*
 * Preference attribute allows specifying that when AppInterface is requested,
 * the Http\Http class should be provided as its implementation.
 */
#[Preference(
    [
        AppInterface::class => \Juzdy\Http\Http::class,
        EventDispatcherInterface::class => \Juzdy\EventBus\EventDispatcher::class,
    ]
)]
// #[PropagatePreference(
//     [
//         EventDispatcherInterface::class => \Juzdy\EventBus\EventDispatcher::class,
//     ]
// )]
class Bootstrap implements BootstrapInterface
{

    /**
     * @param AppInterface $app Resolved application instance
     */
    public function __construct(
        private ContainerInterface $container,
        private AppInterface $app,
        private EventDispatcherInterface $eventDispatcher,
        #[Using(\Juzdy\Http\Event\BeforeRun::class)]
        private EventInterface $beforeStartEvent
        )
    {
    }

    /**
     * Get the dependency injection container
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the application instance
     */
    protected function getApp(): AppInterface
    {
        return $this->app;
    }

    /**
     * Boot the application
     */
    public function boot(): void
    {

        $this->composerUp();

        $app = $this->getApp();

        $this->getEventDispatcher()
            ->dispatch(
                $this->getBeforeStartEvent()
                    ->attach('app', $app)
            );

        $app->run();
    }

    protected function composerUp(): void
    {
        if (!Config::get('bootstrap.discover', false)) {
            return;
        }

        $psr4 = Composer::namespaces();

        foreach ($psr4 as $namespace) {
            $packageBootstrapClass = '\\' . $namespace . 'Bootstrap';
            try {

                if ($this instanceof $packageBootstrapClass) {
                    continue;
                }

                if (!is_a($packageBootstrapClass, BootstrapInterface::class, true)) {
                    continue;
                }
                $bootstrap = $this->getContainer()->get($packageBootstrapClass);
                $bootstrap->boot();

            } catch (\Psr\Container\NotFoundExceptionInterface) {
                continue;
            }
        }
            

    }


    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    protected function getBeforeStartEvent(): EventInterface
    {
        return $this->beforeStartEvent;
    }

}