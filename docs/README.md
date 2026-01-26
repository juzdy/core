# JUZDY Core Documentation

Welcome to the comprehensive documentation for JUZDY Core, a modern PHP 8.2+ framework focused on clean architecture, dependency injection, and event-driven design.

## Table of Contents

### Getting Started
- **[Installation & Setup](./getting-started.md)** - Get up and running quickly with JUZDY Core
- **[Architecture Overview](./architecture.md)** - Understand the framework's design and structure

### Core Components

#### Foundation
- **[Dependency Injection Container](./container.md)** - Advanced DI with plugin architecture
  - Plugin system and lifecycle management
  - Attribute-based configuration
  - Circular dependency detection
  - Lazy loading support

#### Application Layer
- **[HTTP Handling & Middleware](./http.md)** - Request/response lifecycle
  - Request and Response objects
  - Handlers (controllers)
  - Routing system
  - PSR-15 middleware pipeline
  - Built-in middleware (CORS, rate limiting, security)

- **[Event System](./events.md)** - PSR-14 event-driven architecture
  - Event dispatcher and listeners
  - Event propagation control
  - Built-in events
  - Creating custom events

#### Data & Persistence
- **[Model & Database](./model.md)** - ORM and database access
  - Active Record pattern
  - CRUD operations
  - Lifecycle hooks
  - Collections
  - PDO-based connections

#### Presentation Layer
- **[Layout & Views](./layout.md)** - Template rendering and assets
  - PHP templates
  - Context management
  - Asset management (CSS/JS)
  - Layout nesting
  - View helpers

#### Command-Line
- **[CLI Commands](./cli.md)** - Command-line applications
  - Creating commands
  - Built-in database commands
  - Argument parsing
  - Interactive input

#### Configuration
- **[Configuration Management](./configuration.md)** - Application configuration
  - File-based configuration
  - Dot notation access
  - Dynamic references
  - Environment variables

## Quick Links

### By Topic

**Getting Started**
- [Installation](./getting-started.md#installation)
- [Basic Setup](./getting-started.md#basic-setup)
- [Your First Application](./getting-started.md#your-first-application)

**Architecture**
- [Design Philosophy](./architecture.md#design-philosophy)
- [Core Architecture](./architecture.md#core-architecture)
- [Request Lifecycle](./architecture.md#request-lifecycle)
- [Design Patterns](./architecture.md#design-patterns)
- [PSR Standards](./architecture.md#psr-standards)

**Dependency Injection**
- [Basic Usage](./container.md#basic-usage)
- [Auto-Wiring](./container.md#auto-wiring)
- [Plugin System](./container.md#plugin-system)
- [Attributes](./container.md#attributes)
- [Lifecycle Management](./container.md#lifecycle-management)

**HTTP**
- [Request & Response](./http.md#request--response)
- [Handlers](./http.md#handlers)
- [Routing](./http.md#routing)
- [Middleware](./http.md#middleware)
- [Built-in Middleware](./http.md#built-in-middleware)

**Events**
- [Basic Usage](./events.md#basic-usage)
- [Event Classes](./events.md#event-classes)
- [Creating Custom Events](./events.md#custom-events)
- [Event Propagation](./events.md#event-propagation)

**Models**
- [Creating Models](./model.md#creating-a-model)
- [CRUD Operations](./model.md#crud-operations)
- [Lifecycle Hooks](./model.md#lifecycle-hooks)
- [Collections](./model.md#collections)

**Views**
- [Basic Usage](./layout.md#basic-usage)
- [Context Management](./layout.md#context-management)
- [Templates](./layout.md#templates)
- [Asset Management](./layout.md#asset-management)

**CLI**
- [Creating Commands](./cli.md#creating-commands)
- [Built-in Commands](./cli.md#built-in-commands)
- [Command Arguments](./cli.md#command-arguments)

**Configuration**
- [Loading Configuration](./configuration.md#loading-configuration)
- [Accessing Configuration](./configuration.md#accessing-configuration)
- [Dynamic References](./configuration.md#dynamic-references)
- [Environment Configuration](./configuration.md#environment-configuration)

## Framework Overview

### What is JUZDY Core?

JUZDY Core is a sophisticated PHP framework designed for modern web application development with these key features:

- **PHP 8.2+** - Leverages latest PHP features including attributes, enums, and typed properties
- **PSR-Compliant** - Follows PSR-4, PSR-11, PSR-14, PSR-15 standards
- **Plugin-Based DI** - Extensible dependency injection with plugin architecture
- **Event-Driven** - Loose coupling through comprehensive event system
- **Lightweight ORM** - Active Record pattern with lifecycle hooks
- **Middleware Support** - PSR-15 HTTP middleware pipeline
- **Convention over Configuration** - Sensible defaults with flexibility

### Key Features

#### Advanced Dependency Injection
The container is the heart of JUZDY Core, providing:
- Automatic dependency resolution through reflection
- Plugin-based architecture for extensibility
- Attribute-driven configuration (`#[Preference]`, `#[Shared]`, `#[Using]`)
- Circular dependency detection
- Lazy loading with ghost proxies
- Context-aware resolution

[Learn more about the Container →](./container.md)

#### Event-Driven Architecture
Build loosely coupled applications with:
- PSR-14 compliant event dispatcher
- Event propagation control
- Listener registry
- Immutable event objects
- Context-based event data

[Learn more about Events →](./events.md)

#### HTTP Layer
Complete request/response handling with:
- PSR-7 compatible Request/Response
- PSR-15 middleware pipeline
- Automatic routing
- Handler (controller) abstraction
- Built-in CORS, rate limiting, and security middleware

[Learn more about HTTP →](./http.md)

#### Database & ORM
Lightweight but powerful data layer:
- Active Record pattern
- Lifecycle hooks for all operations
- Automatic field validation
- Collection support
- PDO-based connections

[Learn more about Models →](./model.md)

### Architecture

JUZDY Core follows a layered architecture:

```
┌─────────────────────────────────────────┐
│        Application Layer                │
│  (HTTP App, CLI App, Custom Apps)       │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│         Framework Core                  │
│  • Bootstrap & Configuration            │
│  • DI Container                         │
│  • Event Bus                            │
│  • Registry                             │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│         Domain Layer                    │
│  • Models (ORM)                         │
│  • Handlers (Controllers)               │
│  • Services (Business Logic)            │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│      Infrastructure Layer               │
│  • Database (PDO)                       │
│  • HTTP I/O                             │
│  • File System                          │
└─────────────────────────────────────────┘
```

[Learn more about Architecture →](./architecture.md)

## Learning Path

### For Beginners

1. **[Getting Started](./getting-started.md)** - Install and create your first application
2. **[Architecture Overview](./architecture.md)** - Understand the framework structure
3. **[HTTP Handling](./http.md)** - Learn about handlers and routing
4. **[Model & Database](./model.md)** - Work with data
5. **[Layout & Views](./layout.md)** - Render templates

### For Intermediate Users

1. **[Dependency Injection](./container.md)** - Master the DI container
2. **[Event System](./events.md)** - Build event-driven features
3. **[Middleware](./http.md#middleware)** - Process requests and responses
4. **[CLI Commands](./cli.md)** - Create command-line tools
5. **[Configuration](./configuration.md)** - Advanced configuration patterns

### For Advanced Users

1. **[Plugin System](./container.md#plugin-system)** - Extend the container
2. **[Custom Middleware](./http.md#custom-middleware)** - Build middleware
3. **[Lifecycle Hooks](./model.md#lifecycle-hooks)** - Model event handling
4. **[Custom Events](./events.md#custom-events)** - Design event architecture
5. **[Architecture Patterns](./architecture.md#design-patterns)** - Best practices

## Code Examples

### Simple Handler
```php
use Juzdy\Http\Handler;

class WelcomeHandler extends Handler
{
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->response([
            'message' => 'Welcome to JUZDY Core!'
        ]);
    }
}
```

### Model with Lifecycle Hooks
```php
use Juzdy\Model;

class User extends Model
{
    protected string $table = 'users';
    
    protected function _beforeSave(): void
    {
        if ($this->has('password')) {
            $this->set('password', password_hash(
                $this->get('password'),
                PASSWORD_DEFAULT
            ));
        }
    }
}
```

### Event Listener
```php
$dispatcher->addListener(
    UserCreatedEvent::class,
    function(UserCreatedEvent $event) {
        $user = $event->getUser();
        $this->sendWelcomeEmail($user);
    }
);
```

### Dependency Injection
```php
class UserService
{
    public function __construct(
        private UserRepository $repository,
        private EventDispatcherInterface $events
    ) {
    }
}
```

## Additional Resources

- **[Main README](../README.md)** - Project overview and quick start
- **[GitHub Repository](https://github.com/juzdy/core)** - Source code
- **[License](../LICENSE)** - MIT License

## Contributing

We welcome contributions! If you find issues in the documentation or have suggestions for improvements, please:

1. Check existing issues
2. Create a detailed issue or pull request
3. Follow the code style and documentation format

## Support

For questions and support:
- Open an issue on GitHub
- Check the documentation sections above
- Review code examples in the repository

---

**Version:** 1.1.0  
**Last Updated:** January 2026  
**Author:** Victor Galitsky

[← Back to Main README](../README.md)
