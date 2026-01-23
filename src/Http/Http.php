<?php
namespace Juzdy\Http;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Juzdy\Config;
use Juzdy\AppInterface;
use Juzdy\Container\Attribute\Parameter\Using;
use Juzdy\Container\Contract\InjectableInterface;
use Juzdy\Container\Contract\LazyGhostInterface;
use Juzdy\Container\Contract\Lifecycle\PrototypeInterface;
use Juzdy\Container\Contract\Lifecycle\SharedInterface;
use Juzdy\Container\Contract\NothingInterface;
use Juzdy\EventBus\Event\EventInterface;
use Juzdy\EventBus\EventDispatcher;
use Juzdy\Http\Event\BeforeRun;
use Juzdy\Http\Exception\NotFoundException;
use Juzdy\Http\HandlerInterface;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;
use Juzdy\Http\Middleware\MiddlewarePipeline;
use Juzdy\Http\Middleware\MiddlewareInterface;

/**
 * HTTP Application implementation
 *
 * @package Juzdy\Http
 */
class Http implements AppInterface, InjectableInterface, PrototypeInterface, SharedInterface
{
    /**
     * @var RequestInterface|null
     */
    //protected ?RequestInterface $request = null;

    /**
     * @param ContainerInterface $container Dependency injection container
     * @param MiddlewarePipeline $pipeline Middleware pipeline
     * @param Router|null $router Router instance
     */
    
    public function __construct(
        //private NothingInterface $nothing,
        private ContainerInterface $container,
        private RequestInterface $request,
        #[Using(EventDispatcher::class)]
        private EventDispatcherInterface $eventDispatcher,
        private MiddlewarePipeline $pipeline,
        #[Using(BeforeRun::class)]
        private EventInterface $beforeRunEvent,
    ) {}

    public function __clone(): void
    {
        // Custom clone logic if needed
    }

    /**
     * Add middleware to the application pipeline.
     *
     * @param MiddlewareInterface $middleware
     * @return self
     */
    public function addMiddleware(MiddlewareInterface $middleware): self
    {
        $this->getPipeline()->pipe($middleware);
        return $this;
    }
    

    /**
     * Run the application.
     *
     * @return void
     */
    public function run(): void
    {
        // Dispatch before run event
        $this->getEventDispatcher()->dispatch($this->getBeforeRunEvent());
        
        try{

            $this->loadGlobalMiddleware();
            
            $response = $this->handleRequest();

            $response->send();

        } catch (\Throwable $e) {
            
            throw new \Exception($e);
        }
        
    }

    /**
     * Handle the incoming HTTP request.
     *
     * @return ResponseInterface
     */
    protected function handleRequest(): ResponseInterface
    {
        // 
        $this->getPipeline()->setFallbackHandler(
            //$this->getRouter()
            new class () implements HandlerInterface {
                public function handle(RequestInterface $request): ResponseInterface
                {
                    throw new NotFoundException("last handler reached, Response not generated.");
                }
            }
        );
        
        // Process the request through the middleware pipeline
        return $this->getPipeline()->handle($this->getRequest());
    }

    /**
     * Get the dependency injection container.
     *
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     @todo: to be removed and use as dependency only in constructor
     * Get the current HTTP request.
     *
     * @return RequestInterface
     */
    protected function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Get the middleware pipeline.
     *
     * @return MiddlewarePipeline
     */
    public function getPipeline(): MiddlewarePipeline
    {
        return $this->pipeline;
    }

    /**
     * Load global middleware from configuration.
     *
     * @return void
     */
    private function loadGlobalMiddleware(): void
    {
        $middleware = Config::get('middleware.global', []);

        foreach ($middleware as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                try {
                    $middleware = $this->getContainer()->get($middlewareClass);
                } catch (NotFoundExceptionInterface) {
                    throw new \Exception("Middleware class {$middlewareClass} could not be resolved.");
                }
                $this->getPipeline()->pipe($middleware);
            }
        }
    }

    /**
     * Get the event dispatcher.
     *
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Get the before run event.
     *
     * @return EventInterface
     */
    protected function getBeforeRunEvent(): EventInterface
    {
        return $this->beforeRunEvent->attach('app', $this);
    }
    
}