# Architecture Overview

This document provides a comprehensive overview of JUZDY Core's architecture, design patterns, and component interactions.

## Table of Contents

- [Design Philosophy](#design-philosophy)
- [Core Architecture](#core-architecture)
- [Component Diagram](#component-diagram)
- [Request Lifecycle](#request-lifecycle)
- [Design Patterns](#design-patterns)
- [PSR Standards](#psr-standards)

## Design Philosophy

JUZDY Core is built on these fundamental principles:

### 1. **Pragmatic, Not Over-Engineered**
The framework provides powerful features without unnecessary complexity. Every component serves a clear purpose.

### 2. **Attribute-Driven Configuration**
Leveraging PHP 8.2's attributes for clean, declarative configuration:
- `#[Preference]` - Interface to implementation bindings
- `#[Shared]` - Singleton lifecycle management
- `#[Using]` - Explicit dependency selection

### 3. **Plugin-Based Extensibility**
The DI container uses a plugin architecture allowing you to extend core functionality without modifying the framework.

### 4. **Event-Driven Architecture**
Loose coupling through events enables flexible application design and easy integration of cross-cutting concerns.

### 5. **Type Safety**
Full PHP 8.2+ type declarations ensure reliability and better IDE support.

## Core Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   HTTP App   │  │   CLI App    │  │  Custom App  │     │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘     │
│         │                  │                  │             │
│         └──────────────────┴──────────────────┘             │
└────────────────────────────┬────────────────────────────────┘
                             │
┌────────────────────────────┴────────────────────────────────┐
│                      FRAMEWORK CORE                         │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │              Bootstrap & Configuration               │  │
│  │  • Application initialization                        │  │
│  │  • Config loading                                    │  │
│  │  • Package discovery                                 │  │
│  └───────────────────┬──────────────────────────────────┘  │
│                      │                                      │
│  ┌──────────────────┴──────────────────────────────────┐  │
│  │         Dependency Injection Container              │  │
│  │  • Service resolution                               │  │
│  │  • Lifecycle management                             │  │
│  │  • Plugin pipeline                                  │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Event Bus   │  │   Registry   │  │    Debug     │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└──────────────────────────────────────────────────────────────┘
                             │
┌────────────────────────────┴────────────────────────────────┐
│                     DOMAIN LAYER                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │    Models    │  │   Handlers   │  │   Services   │     │
│  │   (ORM)      │  │ (Controllers)│  │  (Business)  │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└──────────────────────────────────────────────────────────────┘
                             │
┌────────────────────────────┴────────────────────────────────┐
│                  INFRASTRUCTURE LAYER                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   Database   │  │   HTTP I/O   │  │  File System │     │
│  │    (PDO)     │  │ (Superglobals)│  │   (Views)    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└──────────────────────────────────────────────────────────────┘
```

## Component Diagram

### Dependency Injection Container

The heart of the framework:

```
┌────────────────────────────────────────────────┐
│         JuzdyContainerInterface                │
│  ┌──────────────────────────────────────────┐ │
│  │  Container (Plugin Pipeline)              │ │
│  │                                           │ │
│  │  ┌─────────────────────────────────────┐ │ │
│  │  │   Plugin Manager (5 types)          │ │ │
│  │  │                                     │ │ │
│  │  │  1. Resolve Plugins                │ │ │
│  │  │     • TypeResolver                 │ │ │
│  │  │     • InterfaceResolver            │ │ │
│  │  │     • AttributeResolver            │ │ │
│  │  │                                     │ │ │
│  │  │  2. Factory Plugins                │ │ │
│  │  │     • StandardFactory              │ │ │
│  │  │     • LazyGhostFactory             │ │ │
│  │  │     • FallbackFactory              │ │ │
│  │  │                                     │ │ │
│  │  │  3. Aware Plugins                  │ │ │
│  │  │     • Injector (method injection)  │ │ │
│  │  │                                     │ │ │
│  │  │  4. Lifecycle Plugins              │ │ │
│  │  │     • Prototype (new each time)    │ │ │
│  │  │     • Shared (singleton)           │ │ │
│  │  │                                     │ │ │
│  │  │  5. Require Plugins                │ │ │
│  │  │     • Dependency validators        │ │ │
│  │  └─────────────────────────────────────┘ │ │
│  └──────────────────────────────────────────┘ │
└────────────────────────────────────────────────┘
```

**Key Features:**
- **Circular Dependency Detection**: Stack tracking prevents infinite loops
- **Context Propagation**: Preferences cascade through resolution chains
- **Lazy Loading**: Ghost proxies for deferred instantiation
- **Attribute-Based Config**: Declarative dependency configuration

### HTTP Request Flow

```
┌─────────────────────────────────────────────────────────────┐
│                      Client Request                         │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  HTTP Application (Juzdy\Http\Http)                         │
│  • Capture superglobals                                     │
│  • Create Request object                                    │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  Middleware Pipeline (PSR-15)                               │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  1. CORS Middleware                                    │ │
│  │     → Add CORS headers                                 │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  2. Rate Limit Middleware                              │ │
│  │     → Check request rate                               │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  3. Security Headers Middleware                        │ │
│  │     → Add security headers                             │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  4. Auth Middleware (optional)                         │ │
│  │     → Validate authentication                          │ │
│  └────────────────────────────────────────────────────────┘ │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  Router (Juzdy\Http\Router)                                 │
│  • Match request to handler                                 │
│  • Extract route parameters                                 │
│  • Resolve handler from container                           │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  Handler (Your Application Logic)                           │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Event: BeforeHandle                                   │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  Business Logic                                        │ │
│  │  • Validate input                                      │ │
│  │  • Interact with models                                │ │
│  │  • Process data                                        │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │  Event: AfterHandle                                    │ │
│  └────────────────────────────────────────────────────────┘ │
└────────────────────────┬────────────────────────────────────┘
                         │
            ┌────────────┴────────────┐
            │                         │
            ▼                         ▼
┌─────────────────────┐   ┌─────────────────────┐
│  Model Layer        │   │  Layout/View        │
│  • Database queries │   │  • Render templates │
│  • Data validation  │   │  • Manage assets    │
│  • Lifecycle hooks  │   │  • Context vars     │
└─────────┬───────────┘   └──────────┬──────────┘
          │                          │
          └──────────────┬───────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  Response (Juzdy\Http\Response)                             │
│  • Status code                                              │
│  • Headers                                                  │
│  • Body (JSON/HTML/etc)                                     │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                      Client Response                        │
└─────────────────────────────────────────────────────────────┘
```

## Request Lifecycle

### 1. Bootstrap Phase

```php
// Entry point: public/index.php
$container = new Container();
$bootstrap = $container->get(Bootstrap::class);
$bootstrap->boot();
```

**What happens:**
1. Container initializes with core plugins
2. Configuration files loaded
3. Package discovery (if enabled)
4. BeforeStart event dispatched
5. Application instance resolved

### 2. Request Phase

```php
// In Http::run()
$request = new Request();  // Capture $_GET, $_POST, $_SERVER, etc.
$response = $this->processMiddleware($request);
```

**What happens:**
1. Request object created from superglobals
2. Middleware pipeline processes request
3. Router matches request to handler
4. Handler resolved from container

### 3. Execution Phase

```php
// In Handler
public function handle(RequestInterface $request): ResponseInterface
{
    // Your application logic
    return $this->response($data);
}
```

**What happens:**
1. BeforeHandle event dispatched
2. Handler dependencies injected
3. Business logic executes
4. Models/services interact
5. AfterHandle event dispatched

### 4. Response Phase

**What happens:**
1. Response object constructed
2. View rendered (if applicable)
3. Headers sent
4. Body output
5. AfterRun event dispatched

## Design Patterns

### 1. **Dependency Injection**
All components receive dependencies through constructor injection, promoting testability and loose coupling.

```php
class UserHandler extends Handler
{
    public function __construct(
        private UserRepository $repository,
        private EventDispatcherInterface $events
    ) {
    }
}
```

### 2. **Service Locator**
The container acts as a central registry for services.

```php
$service = $container->get(MyService::class);
```

### 3. **Plugin Architecture**
Container behavior extended through plugins without modifying core code.

```php
class CustomResolverPlugin implements PluginInterface
{
    public function __invoke($id, ContextInterface $context)
    {
        // Custom resolution logic
    }
}
```

### 4. **Middleware Pipeline** (Chain of Responsibility)
Request processing through a chain of middleware handlers.

```php
$pipeline = new MiddlewarePipeline([
    new CorsMiddleware(),
    new AuthMiddleware(),
    new RateLimitMiddleware(),
]);
```

### 5. **Active Record**
Models handle their own persistence.

```php
$user = new User();
$user->set('name', 'John');
$user->save();  // Automatically inserts or updates
```

### 6. **Observer Pattern** (Events)
Event-driven communication between components.

```php
$dispatcher->dispatch(new UserCreatedEvent($user));
```

### 7. **Factory Pattern**
Container factories create instances with complex initialization.

```php
class StandardFactory implements PluginInterface
{
    public function __invoke($id, ContextInterface $context)
    {
        return new $id(...$this->resolveConstructorParams($id));
    }
}
```

### 8. **Proxy Pattern** (Lazy Loading)
Lazy ghost factory creates proxy objects for deferred loading.

```php
#[LazyGhost]
class ExpensiveService
{
    // Only instantiated when methods are called
}
```

## PSR Standards

JUZDY Core strictly follows PSR standards:

| Standard | Description | Implementation |
|----------|-------------|----------------|
| **PSR-4** | Autoloading | `Juzdy\` namespace mapped to `src/` |
| **PSR-11** | Container Interface | `JuzdyContainerInterface` extends `ContainerInterface` |
| **PSR-14** | Event Dispatcher | `EventDispatcher` implements `EventDispatcherInterface` |
| **PSR-15** | HTTP Middleware | `MiddlewarePipeline` processes `MiddlewareInterface` |
| **PSR-16** | Simple Cache | Supported through dependencies |

### PSR-11 Compliance

```php
interface JuzdyContainerInterface extends ContainerInterface
{
    public function get(string $id): mixed;      // PSR-11
    public function has(string $id): bool;       // PSR-11
    public function share(string $id, mixed $value): void;  // Extension
    public function can(string $id): bool;       // Extension
}
```

### PSR-14 Compliance

```php
interface EventDispatcherInterface
{
    public function dispatch(object $event): object;
}

interface ListenerProviderInterface
{
    public function getListenersForEvent(object $event): iterable;
}
```

### PSR-15 Compliance

```php
interface MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface;
}
```

## Component Interactions

### Example: Creating a User

```
User Request (POST /user/create)
        │
        ▼
    HTTP Application
        │
        ▼
    Middleware Pipeline
        │
        ▼
    Router → UserCreateHandler
        │
        ├──→ Container resolves dependencies
        │    • UserRepository
        │    • EventDispatcher
        │    • Validator
        │
        ▼
    Handler Execution
        │
        ├──→ Dispatch: BeforeUserCreate event
        ├──→ Validate input data
        ├──→ Create User model
        │    │
        │    ▼
        │    Model::save()
        │    ├──→ _beforeCreate() hook
        │    ├──→ Database::insert()
        │    └──→ _afterCreate() hook
        │
        ├──→ Dispatch: AfterUserCreate event
        │
        ▼
    Return Response
        │
        ▼
    JSON Response to Client
```

## Best Practices

### 1. **Use Dependency Injection**
Always inject dependencies rather than using the service locator pattern directly.

✅ **Good:**
```php
public function __construct(private UserRepository $repo) {}
```

❌ **Avoid:**
```php
public function handle() {
    $repo = $this->container->get(UserRepository::class);
}
```

### 2. **Leverage Events for Cross-Cutting Concerns**
Use events for logging, caching, notifications, etc.

```php
$this->dispatcher->dispatch(new UserCreatedEvent($user));
```

### 3. **Use Attributes for Configuration**
Prefer attributes over procedural configuration.

```php
#[Preference([UserInterface::class => User::class])]
class MyBootstrap {}
```

### 4. **Follow Single Responsibility**
Keep handlers focused on a single action.

### 5. **Use Lifecycle Hooks in Models**
Centralize data validation and transformation in model hooks.

```php
protected function _beforeSave(): void
{
    $this->validateEmail();
    $this->hashPassword();
}
```

## Summary

JUZDY Core's architecture is designed for:
- **Modularity**: Clear separation of concerns
- **Extensibility**: Plugin-based customization
- **Testability**: Dependency injection throughout
- **Standards Compliance**: PSR standards for interoperability
- **Performance**: Lazy loading and efficient resource management
- **Developer Experience**: Type safety and IDE support

---

[← Back to Documentation Index](./README.md) | [Next: Dependency Injection Container →](./container.md)
