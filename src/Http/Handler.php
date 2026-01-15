<?php
namespace Juzdy\Http;

use Juzdy\Container\Attribute\Initializer;
use Juzdy\Http\Handler\MiddlewareTrait;
use Juzdy\Http\Handler\RenderTrait;

abstract class Handler implements HandlerInterface, MiddlewareAwareInterface
{
    use MiddlewareTrait;
    use RenderTrait;

    public static function route(): string
    {
        return Router::route(static::class);
    }

    /**
     * @var ResponseInterface|null
     */
    private ?ResponseInterface $response = null;

    /**
     * Initialization method called by Container after construction.
     *
     * @return void
     */
    #[Initializer]
    protected function init(): void
    {
        $this->registerMiddleware();   
    }

    /**
     * Get or create the response object.
     *
     * @return ResponseInterface
     */
    protected function response(): ResponseInterface
    {
        return $this->response ??= new Response();
    }

    /**
     * {@inheritDoc}
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Redirect to a given URL
     *
     * @param string $url The URL to redirect to
     * @param array $args Query parameters to append
     * 
     * @return ResponseInterface The redirect response
     */
    public function redirect(string $route, array $args = []): ResponseInterface
    {
        if (filter_var($route, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Use relative paths for redirects: $route");
        }

        if (!empty($args)) {
            $route .= (strpos($route, '?') === false ? '?' : '&') . http_build_query($args);
        }

        // Prevent header injection by removing newlines
        $route = preg_replace('/[\r\n]/', '', $route);

        return $this->response()
            ->reset()
            ->status(302)
            ->header('Location', $route);
    }

}
