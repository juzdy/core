# HTTP Handling & Middleware

This document covers JUZDY Core's HTTP layer, including request handling, routing, middleware, and response generation.

## Table of Contents

- [Overview](#overview)
- [Request & Response](#request--response)
- [Handlers](#handlers)
- [Routing](#routing)
- [Middleware](#middleware)
- [HTTP Events](#http-events)
- [Best Practices](#best-practices)

## Overview

JUZDY Core implements a complete HTTP request/response cycle with:
- PSR-7 compatible request/response objects
- PSR-15 middleware pipeline
- Automatic routing based on handler names
- Event-driven lifecycle hooks
- Built-in middleware for common tasks (CORS, rate limiting, security)

### HTTP Application Flow

```
Request → Middleware Pipeline → Router → Handler → Response
```

## Request & Response

### Request Object

The `Request` class wraps PHP superglobals with a clean interface:

```php
use Juzdy\Http\Request;
use Juzdy\Http\RequestInterface;

class MyHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        // Get request method
        $method = $request->getMethod();  // GET, POST, PUT, DELETE, etc.
        
        // Get query parameters
        $id = $request->getQuery('id');
        $page = $request->getQuery('page', 1);  // With default value
        
        // Get POST data
        $name = $request->getPost('name');
        $email = $request->getPost('email');
        
        // Get all query parameters
        $queryParams = $request->getQueryParams();
        
        // Get all POST data
        $postData = $request->getParsedBody();
        
        // Get request URI
        $uri = $request->getUri();
        
        // Get headers
        $contentType = $request->getHeader('Content-Type');
        $allHeaders = $request->getHeaders();
        
        // Check if request is AJAX
        if ($request->isXmlHttpRequest()) {
            // Handle AJAX request
        }
        
        return $this->response(['status' => 'ok']);
    }
}
```

### Response Object

The `Response` class provides a fluent interface for building HTTP responses:

```php
use Juzdy\Http\Response;

// JSON response (most common)
$response = new Response();
$response->setStatusCode(200)
         ->setHeader('Content-Type', 'application/json')
         ->setBody(json_encode(['message' => 'Success']));

// Or use the helper method in Handler:
return $this->response(['message' => 'Success'], 200);

// HTML response
return $this->response('<h1>Hello World</h1>', 200, [
    'Content-Type' => 'text/html'
]);

// Redirect
return $this->redirect('/dashboard');

// Error response
return $this->response(['error' => 'Not found'], 404);
```

### Request Methods

Common HTTP methods:

```php
public function handle(RequestInterface $request): ResponseInterface
{
    $method = $request->getMethod();
    
    return match($method) {
        'GET' => $this->handleGet($request),
        'POST' => $this->handlePost($request),
        'PUT' => $this->handlePut($request),
        'DELETE' => $this->handleDelete($request),
        default => $this->response(['error' => 'Method not allowed'], 405),
    };
}
```

## Handlers

Handlers are controllers that process HTTP requests and return responses.

### Basic Handler

```php
<?php
namespace App\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

class IndexHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->response([
            'message' => 'Welcome to JUZDY Core',
            'version' => '1.1.0'
        ]);
    }
}
```

### Handler with Dependencies

```php
class UserHandler extends Handler
{
    public function __construct(
        private UserRepository $repository,
        private EventDispatcherInterface $events
    ) {
    }
    
    public function handle(RequestInterface $request): ResponseInterface
    {
        $userId = $request->getQuery('id');
        $user = $this->repository->find($userId);
        
        if (!$user) {
            return $this->response(['error' => 'User not found'], 404);
        }
        
        return $this->response($user->toArray());
    }
}
```

### Handler Traits

#### RenderTrait

For rendering templates:

```php
use Juzdy\Http\Handler\RenderTrait;

class PageHandler extends Handler
{
    use RenderTrait;
    
    public function handle(RequestInterface $request): ResponseInterface
    {
        $layout = $this->getLayout();
        $layout->context('title', 'Welcome Page');
        $layout->context('content', 'Hello, World!');
        
        return $this->render('page/index.phtml');
    }
}
```

#### MiddlewareTrait

For handler-specific middleware:

```php
use Juzdy\Http\Handler\MiddlewareTrait;

class AdminHandler extends Handler
{
    use MiddlewareTrait;
    
    protected function getMiddleware(): array
    {
        return [
            new AuthMiddleware(),
            new AdminRoleMiddleware(),
        ];
    }
}
```

### Handler Methods

Handlers extend the base `Handler` class which provides:

```php
// JSON response
protected function response(
    array $data,
    int $status = 200,
    array $headers = []
): ResponseInterface;

// Redirect
protected function redirect(
    string $url,
    int $status = 302
): ResponseInterface;

// Get layout (with RenderTrait)
protected function getLayout(): LayoutInterface;

// Render template (with RenderTrait)
protected function render(
    string $template,
    array $context = []
): ResponseInterface;
```

## Routing

JUZDY Core uses convention-based routing where handler class names map to URLs.

### Automatic Routing

```php
// Class: App\Handler\IndexHandler
// URL: / or /index

// Class: App\Handler\UserHandler
// URL: /user

// Class: App\Handler\User\ProfileHandler
// URL: /user/profile

// Class: App\Handler\Admin\DashboardHandler
// URL: /admin/dashboard
```

### Router Configuration

The router is automatically configured but can be customized:

```php
use Juzdy\Http\Router;
use Juzdy\Http\RouterInterface;

class CustomRouter extends Router
{
    protected function matchHandler(RequestInterface $request): string
    {
        // Custom routing logic
        $path = $request->getUri();
        
        // Map paths to handler classes
        return match($path) {
            '/' => IndexHandler::class,
            '/api/users' => ApiUserHandler::class,
            default => $this->defaultMatch($path),
        };
    }
}
```

### Route Parameters

Extract parameters from the URL:

```php
class UserHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        // /user?id=123
        $userId = $request->getQuery('id');
        
        // Or use path parameters (requires custom router)
        // /user/123
        $userId = $request->getAttribute('id');
        
        $user = $this->repository->find($userId);
        return $this->response($user->toArray());
    }
}
```

## Middleware

Middleware processes requests before they reach handlers and responses before they're sent to clients.

### Built-in Middleware

#### CORS Middleware

Handles Cross-Origin Resource Sharing:

```php
use Juzdy\Http\Middleware\CorsMiddleware;

$cors = new CorsMiddleware([
    'origins' => ['https://example.com', 'https://app.example.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization'],
    'credentials' => true,
]);
```

#### Rate Limit Middleware

Prevents abuse by limiting request rates:

```php
use Juzdy\Http\Middleware\RateLimitMiddleware;

$rateLimit = new RateLimitMiddleware([
    'limit' => 100,        // 100 requests
    'window' => 3600,      // Per hour
    'identifier' => 'ip',  // Rate limit by IP address
]);
```

#### Security Headers Middleware

Adds security headers to responses:

```php
use Juzdy\Http\Middleware\SecurityHeadersMiddleware;

$security = new SecurityHeadersMiddleware([
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
]);
```

#### Auth Middleware

Handles authentication:

```php
use Juzdy\Http\Middleware\AuthMiddleware;

$auth = new AuthMiddleware([
    'exclude' => ['/login', '/register', '/public'],
    'redirect' => '/login',
]);
```

### Custom Middleware

Create custom middleware by implementing `MiddlewareInterface`:

```php
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }
    
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $start = microtime(true);
        
        // Log request
        $this->logger->info('Request received', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
        ]);
        
        // Process request
        $response = $handler->handle($request);
        
        // Log response
        $duration = microtime(true) - $start;
        $this->logger->info('Response sent', [
            'status' => $response->getStatusCode(),
            'duration' => $duration,
        ]);
        
        return $response;
    }
}
```

### Middleware Pipeline

Chain multiple middleware:

```php
use Juzdy\Http\Middleware\MiddlewarePipeline;

$pipeline = new MiddlewarePipeline([
    new CorsMiddleware(),
    new SecurityHeadersMiddleware(),
    new LoggingMiddleware($logger),
    new AuthMiddleware(),
    new RateLimitMiddleware(),
]);

$response = $pipeline->process($request, $handler);
```

### Middleware Execution Order

Middleware executes in order:

```
Request Flow (top to bottom):
1. CorsMiddleware       → Process request
2. SecurityMiddleware   → Process request
3. AuthMiddleware       → Process request
4. Handler              → Generate response
5. AuthMiddleware       ← Process response
6. SecurityMiddleware   ← Process response
7. CorsMiddleware       ← Process response
Response sent to client
```

### Conditional Middleware

Apply middleware based on conditions:

```php
class ConditionalAuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $path = $request->getUri()->getPath();
        
        // Skip auth for public routes
        if (str_starts_with($path, '/public/')) {
            return $handler->handle($request);
        }
        
        // Check authentication
        if (!$this->isAuthenticated($request)) {
            return new Response(401, ['error' => 'Unauthorized']);
        }
        
        return $handler->handle($request);
    }
}
```

## HTTP Events

The HTTP layer dispatches events during the request lifecycle:

### BeforeRun Event

Fired before the HTTP application starts processing:

```php
use Juzdy\Http\Event\BeforeRun;

$dispatcher->addListener(BeforeRun::class, function (BeforeRun $event) {
    $app = $event->get('app');
    // Initialize application-level concerns
});
```

### Custom HTTP Events

Create custom events for your handlers:

```php
use Juzdy\EventBus\Event\Event;

class BeforeUserCreateEvent extends Event
{
    public function __construct(
        private array $userData
    ) {
    }
    
    public function getUserData(): array
    {
        return $this->userData;
    }
}

// In handler
class UserCreateHandler extends Handler
{
    public function __construct(
        private EventDispatcherInterface $events
    ) {
    }
    
    public function handle(RequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        // Dispatch event before creating user
        $event = new BeforeUserCreateEvent($data);
        $this->events->dispatch($event);
        
        // Create user...
        
        return $this->response(['status' => 'created']);
    }
}
```

## Best Practices

### 1. Keep Handlers Focused

Each handler should handle one specific action:

✅ **Good:**
```php
class UserCreateHandler extends Handler { }
class UserUpdateHandler extends Handler { }
class UserDeleteHandler extends Handler { }
```

❌ **Avoid:**
```php
class UserHandler extends Handler {
    // Handles create, update, delete, list, etc.
}
```

### 2. Use Dependency Injection

Inject dependencies rather than creating them:

✅ **Good:**
```php
public function __construct(
    private UserRepository $repository
) {
}
```

❌ **Avoid:**
```php
public function handle(RequestInterface $request): ResponseInterface
{
    $repository = new UserRepository();
}
```

### 3. Validate Input

Always validate and sanitize user input:

```php
public function handle(RequestInterface $request): ResponseInterface
{
    $email = $request->getPost('email');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $this->response(['error' => 'Invalid email'], 400);
    }
    
    // Process valid email
}
```

### 4. Use Appropriate Status Codes

```php
200 OK              - Successful GET, PUT
201 Created         - Successful POST (resource created)
204 No Content      - Successful DELETE
400 Bad Request     - Invalid input
401 Unauthorized    - Authentication required
403 Forbidden       - Authenticated but not allowed
404 Not Found       - Resource doesn't exist
422 Unprocessable   - Validation failed
500 Server Error    - Internal error
```

### 5. Handle Exceptions

```php
public function handle(RequestInterface $request): ResponseInterface
{
    try {
        $user = $this->repository->find($id);
        return $this->response($user->toArray());
    } catch (NotFoundException $e) {
        return $this->response(['error' => 'User not found'], 404);
    } catch (Exception $e) {
        $this->logger->error('Error in UserHandler', ['exception' => $e]);
        return $this->response(['error' => 'Internal server error'], 500);
    }
}
```

### 6. Use Middleware for Cross-Cutting Concerns

Don't repeat authentication, logging, etc. in every handler - use middleware:

```php
// Instead of checking auth in every handler
class ProtectedHandler extends Handler
{
    use MiddlewareTrait;
    
    protected function getMiddleware(): array
    {
        return [new AuthMiddleware()];
    }
}
```

## Common Patterns

### RESTful API Handler

```php
class ApiUserHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return match($request->getMethod()) {
            'GET' => $this->index($request),
            'POST' => $this->create($request),
            'PUT' => $this->update($request),
            'DELETE' => $this->delete($request),
            default => $this->response(['error' => 'Method not allowed'], 405),
        };
    }
    
    private function index(RequestInterface $request): ResponseInterface
    {
        $users = $this->repository->all();
        return $this->response(['users' => $users]);
    }
    
    private function create(RequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        $user = $this->repository->create($data);
        return $this->response(['user' => $user], 201);
    }
}
```

### File Upload Handler

```php
class UploadHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        $files = $request->getUploadedFiles();
        
        if (empty($files['file'])) {
            return $this->response(['error' => 'No file uploaded'], 400);
        }
        
        $file = $files['file'];
        
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $this->response(['error' => 'Upload failed'], 400);
        }
        
        $filename = $this->storage->store($file);
        
        return $this->response(['filename' => $filename], 201);
    }
}
```

## Related Documentation

- [Architecture Overview](./architecture.md) - Framework architecture
- [Dependency Injection Container](./container.md) - DI system
- [Events](./events.md) - Event system
- [Model & Database](./model.md) - Data layer

---

[← Back to Documentation Index](./README.md) | [Next: Event System →](./events.md)
