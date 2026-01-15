<?php
namespace Juzdy\Http;

use Juzdy\Http\Middleware\MiddlewareInterface;


interface RouterInterface extends  HandlerInterface, MiddlewareInterface
{
    /**
     * Match the incoming request to a route and return the corresponding handler.
     *
     * @param RequestInterface $request
     * 
     * @return HandlerInterface
     * 
     * @throws NotFoundException If no matching route is found
     */
    public function process(RequestInterface $request, HandlerInterface $handler): ResponseInterface;
    

    /**
     * Handle the request.
     *
     * @param RequestInterface $request
     * 
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request): ResponseInterface;
}
