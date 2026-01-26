# Dependency Injection Container

JUZDY Core features a sophisticated, plugin-based dependency injection container that provides powerful service resolution with type safety and extensibility.

## Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Container Features](#container-features)
- [Plugin System](#plugin-system)
- [Attributes](#attributes)
- [Lifecycle Management](#lifecycle-management)
- [Advanced Topics](#advanced-topics)

## Overview

The DI container is the heart of JUZDY Core, implementing PSR-11 with extensions for:
- Automatic dependency resolution
- Attribute-driven configuration
- Plugin-based extensibility
- Lifecycle management (Prototype vs Shared)
- Circular dependency detection
- Lazy loading support

### Key Interfaces

```php
interface JuzdyContainerInterface extends ContainerInterface
{
    public function get(string $id): mixed;              // PSR-11
    public function has(string $id): bool;               // PSR-11
    public function share(string $id, mixed $value): void;
    public function can(string $id): bool;
}
```

## Basic Usage

### Getting Services

```php
use Juzdy\Container\Container;

$container = new Container();

// Get a service (auto-resolves dependencies)
$service = $container->get(MyService::class);

// Check if service can be resolved
if ($container->has(MyService::class)) {
    $service = $container->get(MyService::class);
}

// Check if container can create service (no exceptions)
if ($container->can(MyService::class)) {
    $service = $container->get(MyService::class);
}
```

### Sharing Instances (Singletons)

```php
// Register a shared instance
$config = new Config(['key' => 'value']);
$container->share(Config::class, $config);

// All subsequent gets return the same instance
$config1 = $container->get(Config::class);
$config2 = $container->get(Config::class);
// $config1 === $config2 === $config (same object)
```

### Auto-Wiring

The container automatically resolves constructor dependencies:

```php
class UserRepository
{
    public function __construct(
        private Database $db,
        private CacheInterface $cache
    ) {
    }
}

// Container automatically injects Database and CacheInterface
$repository = $container->get(UserRepository::class);
```

## Container Features

### 1. Automatic Type Resolution

The container uses reflection to analyze constructor parameters and resolve dependencies automatically:

```php
class UserService
{
    public function __construct(
        private UserRepository $repository,    // Resolved automatically
        private LoggerInterface $logger,       // Resolved from bindings
        private EventDispatcherInterface $events
    ) {
    }
}

$service = $container->get(UserService::class);
```

### 2. Interface Binding

Use the `#[Preference]` attribute to bind interfaces to implementations:

```php
use Juzdy\Container\Attribute\Preference;

#[Preference([
    LoggerInterface::class => FileLogger::class,
    CacheInterface::class => RedisCache::class,
])]
class MyBootstrap extends Bootstrap
{
}
```

### 3. Circular Dependency Detection

The container detects and prevents circular dependencies:

```php
class ServiceA
{
    public function __construct(private ServiceB $b) {}
}

class ServiceB
{
    public function __construct(private ServiceA $a) {}  // Circular!
}

try {
    $container->get(ServiceA::class);
} catch (CircularDependencyException $e) {
    echo "Circular dependency detected: " . $e->getMessage();
}
```

### 4. Context Awareness

Resolution context tracks the dependency chain and preferences:

```php
class Context implements ContextInterface
{
    public function push(string $id): void;
    public function pop(): ?string;
    public function getStack(): array;
    public function getPreferences(): array;
}
```

## Plugin System

The container uses a plugin architecture with five types of plugin managers:

### 1. Resolve Plugins

Handle dependency resolution strategies:

#### TypeResolver
Resolves concrete classes by type:

```php
class MyService
{
    public function __construct(private Database $db) {}
}

// TypeResolver checks if Database is a concrete class
```

#### InterfaceResolver
Resolves interfaces using preferences:

```php
#[Preference([CacheInterface::class => RedisCache::class])]
```

#### AttributeResolver
Resolves based on parameter attributes:

```php
class MyService
{
    public function __construct(
        #[Using(FileLogger::class)]
        private LoggerInterface $logger
    ) {
    }
}
```

### 2. Factory Plugins

Create instances with different strategies:

#### StandardFactory
Default factory using reflection and constructor injection:

```php
class StandardFactory implements PluginInterface
{
    public function __invoke($id, ContextInterface $context)
    {
        $reflection = new ReflectionClass($id);
        $params = $this->resolveConstructorParams($reflection);
        return $reflection->newInstanceArgs($params);
    }
}
```

#### LazyGhostFactory
Creates proxy objects for lazy loading:

```php
use Juzdy\Container\Attribute\LazyGhost;

#[LazyGhost]
class ExpensiveService
{
    // Only instantiated when methods are actually called
    public function __construct(private HeavyDependency $heavy) {}
}
```

#### FallbackFactory
Factory of last resort for edge cases.

### 3. Aware Plugins

Handle post-instantiation concerns:

#### Injector
Method injection via attributes:

```php
use Juzdy\Container\Attribute\Method\Injector;

class MyService
{
    private LoggerInterface $logger;
    
    #[Injector]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
```

### 4. Lifecycle Plugins

Manage instance lifecycle:

#### Prototype
Creates a new instance for every request:

```php
use Juzdy\Container\Contract\Lifecycle\Prototype;

class TransientService implements Prototype
{
    // New instance each time
}
```

#### Shared
Singleton pattern - one instance shared across container:

```php
use Juzdy\Container\Contract\Lifecycle\Shared;

class SingletonService implements Shared
{
    // Same instance always returned
}

// Or use attribute:
use Juzdy\Container\Attribute\Shared as SharedAttribute;

#[SharedAttribute]
class ConfigService
{
}
```

### 5. Require Plugins

Validate dependencies (future extension point).

## Attributes

### @Preference

Bind interfaces to implementations:

```php
use Juzdy\Container\Attribute\Preference;

#[Preference([
    UserRepositoryInterface::class => MySqlUserRepository::class,
    LoggerInterface::class => FileLogger::class,
])]
class Bootstrap
{
}
```

### @PropagatePreference

Propagate preferences through the entire resolution chain:

```php
use Juzdy\Container\Attribute\PropagatePreference;

#[PropagatePreference([
    LoggerInterface::class => DebugLogger::class,
])]
class DebugBootstrap
{
    // All dependencies will use DebugLogger for LoggerInterface
}
```

### @Using

Specify exact implementation for a parameter:

```php
use Juzdy\Container\Attribute\Parameter\Using;

class MyService
{
    public function __construct(
        #[Using(RedisCache::class)]
        private CacheInterface $cache
    ) {
    }
}
```

### @Shared

Mark a class as singleton:

```php
use Juzdy\Container\Attribute\Shared;

#[Shared]
class Configuration
{
    // Only one instance will ever be created
}
```

### @Injector

Method injection after construction:

```php
use Juzdy\Container\Attribute\Method\Injector;

class UserController
{
    private LoggerInterface $logger;
    
    #[Injector]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
```

## Lifecycle Management

### Prototype (Default)

New instance created on every `get()` call:

```php
$service1 = $container->get(MyService::class);
$service2 = $container->get(MyService::class);
// $service1 !== $service2 (different objects)
```

### Shared (Singleton)

Same instance returned on every `get()` call:

```php
use Juzdy\Container\Contract\Lifecycle\Shared;

class DatabaseConnection implements Shared
{
}

$db1 = $container->get(DatabaseConnection::class);
$db2 = $container->get(DatabaseConnection::class);
// $db1 === $db2 (same object)
```

### Manual Sharing

```php
$instance = new MyService();
$container->share(MyService::class, $instance);

// All gets return the same instance
```

## Advanced Topics

### Custom Plugins

Create custom resolution plugins:

```php
use Juzdy\Container\Plugin\PluginInterface;
use Juzdy\Container\Context\ContextInterface;

class CustomResolverPlugin implements PluginInterface
{
    public function __invoke($id, ContextInterface $context)
    {
        // Custom resolution logic
        if ($id === SpecialService::class) {
            return new SpecialService($context);
        }
        
        return null; // Continue to next plugin
    }
}

// Register in PluginManager
$container->getPluginManager('resolve')->add(new CustomResolverPlugin());
```

### Resolution Context

Track and manipulate the resolution chain:

```php
class MyFactory
{
    public function create($id, ContextInterface $context)
    {
        // Get resolution stack
        $stack = $context->getStack();
        
        // Check if we're resolving within a specific context
        if (in_array(SpecialController::class, $stack)) {
            return new SpecialImplementation();
        }
        
        return new StandardImplementation();
    }
}
```

### Contextual Binding

Different implementations based on context:

```php
#[Preference([
    LoggerInterface::class => FileLogger::class,
])]
class WebBootstrap extends Bootstrap
{
}

#[Preference([
    LoggerInterface::class => ConsoleLogger::class,
])]
class CliBootstrap extends Bootstrap
{
}
```

### Lazy Loading

Use lazy ghost factory for expensive dependencies:

```php
use Juzdy\Container\Attribute\LazyGhost;

#[LazyGhost]
class HeavyService
{
    public function __construct(
        private ExpensiveDependency $dep1,
        private AnotherExpensiveDependency $dep2
    ) {
        // Constructor only called when methods are invoked
    }
    
    public function doWork(): void
    {
        // Dependencies instantiated here, not in get()
    }
}
```

### Aliasing

```php
// Share by interface
$container->share(LoggerInterface::class, new FileLogger());

// Now this works
$logger = $container->get(LoggerInterface::class);
```

## Best Practices

### 1. Constructor Injection Over Property Injection

✅ **Good:**
```php
class MyService
{
    public function __construct(private LoggerInterface $logger) {}
}
```

❌ **Avoid:**
```php
class MyService
{
    public LoggerInterface $logger; // Public property injection
}
```

### 2. Use Interfaces

Define and depend on interfaces, not concrete implementations:

```php
interface UserRepositoryInterface
{
    public function find(int $id): User;
}

class UserService
{
    public function __construct(
        private UserRepositoryInterface $repository  // Interface, not concrete
    ) {
    }
}
```

### 3. Avoid Service Locator Pattern

✅ **Good (Constructor Injection):**
```php
class MyHandler
{
    public function __construct(private UserService $service) {}
}
```

❌ **Avoid (Service Locator):**
```php
class MyHandler
{
    public function __construct(private ContainerInterface $container) {}
    
    public function handle()
    {
        $service = $this->container->get(UserService::class);
    }
}
```

### 4. Use Shared Wisely

Only make services shared when they truly need to be singletons (database connections, configuration, etc.). Don't default to shared for everything.

### 5. Prefer Attributes Over Manual Registration

```php
// Better than manually configuring bindings
#[Preference([
    LoggerInterface::class => FileLogger::class,
])]
```

### 6. Document Custom Plugins

If you create custom plugins, document their behavior and when they trigger.

## Common Patterns

### Factory Services

```php
class UserFactory
{
    public function __construct(
        private ContainerInterface $container,
        private EventDispatcherInterface $events
    ) {
    }
    
    public function createUser(array $data): User
    {
        $user = new User();
        $user->fill($data);
        $this->events->dispatch(new UserCreating($user));
        return $user;
    }
}
```

### Repository Pattern

```php
interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    public function save(User $user): void;
}

class MySqlUserRepository implements UserRepositoryInterface
{
    public function __construct(private Database $db) {}
    
    public function find(int $id): ?User
    {
        // Implementation
    }
}

// Register in Bootstrap
#[Preference([
    UserRepositoryInterface::class => MySqlUserRepository::class,
])]
```

### Decorator Pattern

```php
class CachedUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private UserRepositoryInterface $inner,
        private CacheInterface $cache
    ) {
    }
    
    public function find(int $id): ?User
    {
        return $this->cache->remember("user.$id", function() use ($id) {
            return $this->inner->find($id);
        });
    }
}
```

## Troubleshooting

### Issue: Cannot Resolve Interface

**Problem:** `Container cannot resolve SomeInterface`

**Solution:** Register a preference:
```php
#[Preference([SomeInterface::class => ConcreteImplementation::class])]
```

### Issue: Circular Dependency

**Problem:** `Circular dependency detected: A → B → A`

**Solution:** 
1. Refactor to remove circular dependency
2. Use setter injection for one dependency
3. Extract shared logic to a third service

### Issue: Class Not Found

**Problem:** `Class MyClass does not exist`

**Solution:**
1. Check namespace and autoloading
2. Run `composer dump-autoload`
3. Verify PSR-4 configuration in composer.json

## Related Documentation

- [Architecture Overview](./architecture.md) - Understand the framework architecture
- [Getting Started](./getting-started.md) - Basic setup and usage
- [HTTP Handling](./http.md) - How DI works with HTTP handlers
- [Events](./events.md) - Event system integration

---

[← Back to Documentation Index](./README.md) | [Next: HTTP Handling & Middleware →](./http.md)
