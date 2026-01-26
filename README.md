# JUZDY Core

A modern, lightweight, PSR-compliant PHP framework built for PHP 8.2+ with a focus on clean architecture, dependency injection, and event-driven design.

## Overview

JUZDY Core is a sophisticated PHP framework that provides:

- **Advanced Dependency Injection Container** with plugin-based architecture
- **Event-Driven Architecture** following PSR-14 standards
- **HTTP Request/Response Handling** with PSR-15 middleware support
- **Lightweight ORM** with active record pattern and lifecycle hooks
- **Layout/View System** with asset management
- **CLI Support** for command-line applications
- **Configuration Management** with dynamic reference resolution

## Key Features

### 🎯 Modern PHP 8.2+
- Full type safety with typed properties and return types
- Attribute-based configuration (`#[Preference]`, `#[Shared]`, `#[Using]`)
- Enum support and modern PHP features

### 🔌 Plugin-Based DI Container
- Extensible through plugin managers (Resolve, Factory, Aware, Lifecycle, Require)
- Lazy loading support with Ghost Factory
- Circular dependency detection
- Attribute-driven dependency resolution

### 🚀 PSR-Compliant
- **PSR-4**: Autoloading
- **PSR-11**: Container Interface
- **PSR-14**: Event Dispatcher
- **PSR-15**: HTTP Server Middleware
- **PSR-16**: Simple Cache (supported)

### 🎪 Event System
- Full event-driven architecture
- Listener registry and propagation control
- Pre-boot and lifecycle events

### 🌐 HTTP Layer
- Middleware pipeline architecture
- Built-in CORS, rate limiting, and security headers
- Request routing with automatic handler resolution
- PSR-7 compatible request/response

## Quick Start

### Installation

```bash
composer require juzdy/core
```

### Basic Usage

```php
<?php
use Juzdy\Bootstrap;
use Juzdy\Container\Container;

// Create container and bootstrap
$container = new Container();
$bootstrap = $container->get(Bootstrap::class);

// Boot the application
$bootstrap->boot();
```

### Creating a Simple HTTP Handler

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
        return $this->response(['message' => 'Hello, World!']);
    }
}
```

## Documentation

Comprehensive documentation is available in the [docs](./docs) folder:

### Getting Started
- [Installation & Setup](./docs/getting-started.md) - Get up and running quickly
- [Architecture Overview](./docs/architecture.md) - Understand the framework structure

### Core Components
- [Dependency Injection Container](./docs/container.md) - Advanced DI with plugins
- [HTTP Handling & Middleware](./docs/http.md) - Request/response lifecycle
- [Event System](./docs/events.md) - Event-driven architecture
- [Model & Database](./docs/model.md) - ORM and database access
- [Layout & Views](./docs/layout.md) - Template rendering and assets
- [CLI Commands](./docs/cli.md) - Command-line applications
- [Configuration](./docs/configuration.md) - Config management

## Requirements

- PHP >= 8.2
- Composer

### Dependencies

- `psr/container`: ^2.0
- `psr/simple-cache`: ^3.0
- `psr/event-dispatcher`: ^1.0
- `psr/http-server-middleware`: ^1.0
- `psr/http-server-handler`: ^1.0

## Project Structure

```
src/
├── AppInterface.php          # Application interface
├── Bootstrap.php             # Application bootstrapper
├── Container/                # DI Container implementation
│   ├── Container.php
│   ├── Plugin/              # Container plugins
│   ├── Attribute/           # DI attributes
│   └── Context/             # Resolution context
├── Http/                     # HTTP layer
│   ├── Http.php             # HTTP application
│   ├── Router.php           # Request router
│   ├── Handler.php          # Base handler
│   ├── Middleware/          # Middleware implementations
│   └── Event/               # HTTP events
├── EventBus/                 # Event system
│   ├── EventDispatcher.php
│   ├── ListenerProvider.php
│   └── Event/
├── Model/                    # ORM layer
│   └── Model.php
├── Layout/                   # View layer
│   ├── Layout.php
│   ├── Render/
│   └── Asset/
├── Cli/                      # CLI support
│   ├── Application.php
│   └── Command/
└── Config.php                # Configuration manager
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License - see the [LICENSE](LICENSE) file for details.

## Author

Victor Galitsky (concept.galitsky@gmail.com)

## Links

- [GitHub Repository](https://github.com/juzdy/core)
- [Full Documentation](./docs)

---

For detailed documentation on each component, please see the [docs](./docs) folder.
