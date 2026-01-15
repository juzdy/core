<?php
namespace Juzdy\Http;

use Psr\Container\ContainerInterface;
use Juzdy\Config;
use Juzdy\Container\Attribute\Preference;
use Juzdy\Container\Container;
use Juzdy\Http\Exception\NotFoundException;
use Juzdy\Http\Middleware\MiddlewareInterface;

#[Preference(preferences: [ContainerInterface::class => Container::class])]
class Router implements HandlerInterface, MiddlewareInterface
{

    public function __construct(protected ContainerInterface $container)
    {
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public static function route(string $handlerClass): string
    {
        //$class = str_replace(, '', $handlerClass);

        $parts = preg_split('/(?=[A-Z])/', $handlerClass, -1, PREG_SPLIT_NO_EMPTY);
        $routeParts = [];
        foreach ($parts as $part) {
            $routeParts[] = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $part));
        }
        return implode('/', $routeParts);
    }

    public function process(RequestInterface $request, HandlerInterface $handler): ResponseInterface
    {
        return $this->handle($request);
    }
    /**
     * Handle the request.
     * This method is called by the middleware pipeline.
     *
     * @param Request $request
     * @return void
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->dispatch($request);
    }

    /**
     * Dispatch a route to the appropriate controller/action.
     * e.g. 'account/profile' -> \App\Controller\Account\ProfileController
     */
    private function dispatch(RequestInterface $request): ResponseInterface
    {
        $route = $request->query(Config::get('http.htaccess_handler_rewrite_param') ?? uniqid()) ?? '';

        $parts = array_filter(explode('/', $route));

        if (count($parts) < 2) {
            $parts[] = Config::get('http.default_handler') ?? 'index'; // Default handler if not specified
        }
        
        // Convert kebab-case to camelCase for each part
        $parts = array_map(function($part) {
            return preg_replace_callback('/-([a-z])/', function($matches) {
                return strtoupper($matches[1]);
            }, $part);
        }, $parts);
        
        // Build the fully qualified class name
        $route = implode('\\', array_map('ucfirst', $parts));
        // Remove underscores for class names
        $route = str_replace('_', '', $route); 

        $composerNamespaces = \Juzdy\Composer\Composer::namespaces();

        $handlerClasses = [];

        foreach ($composerNamespaces as $namespace) {
            foreach (Config::get('http.request_handlers_namespace') as $handlerNamespace) {
                $possibleHandler = preg_replace(
                    '/\\\\+/',
                    '\\',
                    str_replace('{namespace}', $namespace, $handlerNamespace) . '\\' . $route
                );
                if (class_exists($possibleHandler) && is_subclass_of($possibleHandler, HandlerInterface::class)) {
                    $handlerClasses[] = $possibleHandler;
                }
            }
        }

        if (count($handlerClasses) === 0) {
                throw new NotFoundException("Handler not found for route: $route");
        }

        if (count($handlerClasses) > 1) {
                $handlers = implode(', ', $handlerClasses);
                throw new \RuntimeException("Multiple handlers found for route: $route: $handlers");
        }

        $handlerClass = $handlerClasses[0];


        if (!class_exists($handlerClass) || !is_subclass_of($handlerClass, HandlerInterface::class)) {
                throw new \RuntimeException("Handler not found: $handlerClass");
        }

        // Instantiate the handler via the container
        $handler = $this->getContainer()->get($handlerClass);
        
        // Apply middleware group to controller if applicable
        $this->applyMiddlewareGroup($handler);
        
        // Execute controller with its middleware
        return $handler->executeWithMiddleware(function() use ($handler, $request) {
            return $handler->handle($request);
        }, $request);
    }

    /**
     * Apply middleware group to a controller.
     *
     * @param HandlerInterface $handler
     * @return void
     */
    private function applyMiddlewareGroup(HandlerInterface $handler): void
    {
        $groups = array_merge(
            [$handler::class],
            class_parents($handler),
            class_implements($handler),
        );

        foreach ($groups as $group) {
            
            $middlewareClasses = Config::get("middleware.groups.{$group}") ?? [];
            
            foreach ($middlewareClasses as $middlewareClass) {
                if (class_exists($middlewareClass)) {
                    $handler->addMiddleware(new $middlewareClass());
                }
            }
        }
    }

}
