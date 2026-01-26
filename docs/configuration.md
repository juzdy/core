# Configuration Management

JUZDY Core provides a simple yet powerful configuration system with file-based storage, dynamic reference resolution, and dot-notation access.

## Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Configuration Files](#configuration-files)
- [Dynamic References](#dynamic-references)
- [Environment Configuration](#environment-configuration)
- [Best Practices](#best-practices)

## Overview

The configuration system provides:
- **File-based configuration** - PHP arrays in config files
- **Glob pattern loading** - Load multiple files at once
- **Dot notation access** - Easy nested value access
- **Dynamic references** - Reference other config values
- **Array merging** - Combine multiple config files
- **Environment variables** - Support for environment-based config

## Basic Usage

### Loading Configuration

```php
use Juzdy\Config;

// Load single file
Config::load(__DIR__ . '/config/app.php');

// Load multiple files with glob pattern
Config::load(__DIR__ . '/config/*.php');

// Load specific files
Config::load([
    __DIR__ . '/config/app.php',
    __DIR__ . '/config/database.php',
    __DIR__ . '/config/services.php',
]);
```

### Accessing Configuration

```php
// Get value with dot notation
$appName = Config::get('app.name');
$dbHost = Config::get('database.host');

// Get with default value
$debug = Config::get('app.debug', false);
$timeout = Config::get('api.timeout', 30);

// Get entire array
$dbConfig = Config::get('database');
// ['host' => '...', 'port' => ..., 'database' => '...']

// Check if key exists
if (Config::has('app.debug')) {
    // Key exists
}
```

### Setting Configuration

```php
// Set value
Config::set('app.name', 'My Application');

// Set nested value
Config::set('database.host', 'localhost');

// Set array
Config::set('cache', [
    'driver' => 'redis',
    'host' => 'localhost',
    'port' => 6379,
]);
```

## Configuration Files

### File Structure

```
config/
├── app.php          # Application settings
├── database.php     # Database configuration
├── cache.php        # Cache settings
├── mail.php         # Email configuration
└── services.php     # Service providers
```

### App Configuration

```php
// config/app.php
<?php
return [
    'app' => [
        'name' => 'JUZDY Application',
        'env' => 'production',
        'debug' => false,
        'url' => 'https://example.com',
        'timezone' => 'UTC',
        'locale' => 'en',
    ],
];
```

### Database Configuration

```php
// config/database.php
<?php
return [
    'database' => [
        'default' => 'mysql',
        
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'myapp',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ],
            
            'postgres' => [
                'driver' => 'pgsql',
                'host' => 'localhost',
                'port' => 5432,
                'database' => 'myapp',
                'username' => 'postgres',
                'password' => '',
            ],
        ],
    ],
];
```

### Cache Configuration

```php
// config/cache.php
<?php
return [
    'cache' => [
        'default' => 'file',
        
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => __DIR__ . '/../storage/cache',
            ],
            
            'redis' => [
                'driver' => 'redis',
                'host' => 'localhost',
                'port' => 6379,
                'database' => 0,
            ],
        ],
    ],
];
```

### Services Configuration

```php
// config/services.php
<?php
return [
    'services' => [
        'mail' => [
            'driver' => 'smtp',
            'host' => 'smtp.mailtrap.io',
            'port' => 2525,
            'username' => 'username',
            'password' => 'password',
            'encryption' => 'tls',
        ],
        
        'stripe' => [
            'key' => 'pk_test_...',
            'secret' => 'sk_test_...',
        ],
        
        'aws' => [
            'key' => 'AKIAIOSFODNN7EXAMPLE',
            'secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'region' => 'us-east-1',
            'bucket' => 'my-bucket',
        ],
    ],
];
```

## Dynamic References

### Reference Syntax

Use `@{key}` to reference other configuration values:

```php
// config/app.php
return [
    'app' => [
        'name' => 'My App',
        'url' => 'https://example.com',
    ],
    
    'api' => [
        'url' => '@{app.url}/api',  // References app.url
        'name' => '@{app.name} API', // References app.name
    ],
];

// When accessed:
Config::get('api.url');   // Returns: 'https://example.com/api'
Config::get('api.name');  // Returns: 'My App API'
```

### Nested References

```php
return [
    'paths' => [
        'root' => '/var/www/html',
        'storage' => '@{paths.root}/storage',
        'cache' => '@{paths.storage}/cache',
        'logs' => '@{paths.storage}/logs',
    ],
];

// Results:
// paths.storage => '/var/www/html/storage'
// paths.cache   => '/var/www/html/storage/cache'
// paths.logs    => '/var/www/html/storage/logs'
```

### Environment Variable References

```php
return [
    'database' => [
        'host' => '@{DB_HOST}',      // From environment variable
        'port' => '@{DB_PORT}',
        'database' => '@{DB_NAME}',
        'username' => '@{DB_USER}',
        'password' => '@{DB_PASS}',
    ],
];
```

## Environment Configuration

### Using .env Files

```bash
# .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASS=secret

CACHE_DRIVER=redis
REDIS_HOST=localhost
REDIS_PORT=6379
```

### Loading Environment Variables

```php
// Load .env file (using vlucas/phpdotenv or similar)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Then in config files, reference environment variables
return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    ],
];
```

### Environment-Specific Config

```php
// Load environment-specific config
$env = $_ENV['APP_ENV'] ?? 'production';

Config::load(__DIR__ . "/config/app.php");
Config::load(__DIR__ . "/config/app.{$env}.php"); // app.development.php, app.production.php

// config/app.development.php
return [
    'app' => [
        'debug' => true,
        'log_level' => 'debug',
    ],
];

// config/app.production.php
return [
    'app' => [
        'debug' => false,
        'log_level' => 'error',
    ],
];
```

## Advanced Usage

### Configuration Caching

```php
class ConfigCache
{
    public static function cache(): void
    {
        $config = Config::all();
        file_put_contents(
            __DIR__ . '/cache/config.php',
            '<?php return ' . var_export($config, true) . ';'
        );
    }
    
    public static function load(): void
    {
        $cached = include __DIR__ . '/cache/config.php';
        foreach ($cached as $key => $value) {
            Config::set($key, $value);
        }
    }
}

// In production
if (file_exists(__DIR__ . '/cache/config.php')) {
    ConfigCache::load();
} else {
    Config::load(__DIR__ . '/config/*.php');
    ConfigCache::cache();
}
```

### Validation

```php
class ConfigValidator
{
    public static function validate(): void
    {
        $required = [
            'app.name',
            'app.url',
            'database.host',
            'database.database',
        ];
        
        foreach ($required as $key) {
            if (!Config::has($key)) {
                throw new \RuntimeException("Required config missing: {$key}");
            }
        }
    }
}

// Call after loading config
Config::load(__DIR__ . '/config/*.php');
ConfigValidator::validate();
```

### Type-Safe Config Classes

```php
class AppConfig
{
    public static function name(): string
    {
        return Config::get('app.name');
    }
    
    public static function debug(): bool
    {
        return (bool) Config::get('app.debug', false);
    }
    
    public static function url(): string
    {
        return rtrim(Config::get('app.url'), '/');
    }
}

class DatabaseConfig
{
    public static function host(): string
    {
        return Config::get('database.host', 'localhost');
    }
    
    public static function port(): int
    {
        return (int) Config::get('database.port', 3306);
    }
    
    public static function database(): string
    {
        return Config::get('database.database');
    }
}

// Usage
$appName = AppConfig::name();
$dbHost = DatabaseConfig::host();
```

## Best Practices

### 1. Organize by Concern

```
config/
├── app.php          # Core application settings
├── database.php     # Database connections
├── cache.php        # Caching configuration
├── mail.php         # Email settings
├── queue.php        # Queue configuration
└── services.php     # Third-party services
```

### 2. Use Environment Variables for Secrets

✅ **Good:**
```php
return [
    'database' => [
        'password' => $_ENV['DB_PASS'],
        'api_key' => $_ENV['API_KEY'],
    ],
];
```

❌ **Never:**
```php
return [
    'database' => [
        'password' => 'supersecret123',  // Don't commit secrets!
    ],
];
```

### 3. Provide Sensible Defaults

```php
return [
    'cache' => [
        'ttl' => $_ENV['CACHE_TTL'] ?? 3600,
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
    ],
];
```

### 4. Document Configuration Options

```php
return [
    'app' => [
        // Application name displayed in UI
        'name' => 'My App',
        
        // Enable debug mode (never in production)
        'debug' => false,
        
        // Application URL (without trailing slash)
        'url' => 'https://example.com',
        
        // Timezone for dates (PHP timezone identifier)
        'timezone' => 'UTC',
    ],
];
```

### 5. Validate Required Configuration

```php
// bootstrap.php
Config::load(__DIR__ . '/config/*.php');

// Ensure required config is present
$required = ['app.name', 'database.host', 'database.database'];
foreach ($required as $key) {
    if (!Config::has($key)) {
        die("Missing required configuration: {$key}");
    }
}
```

### 6. Use Type-Safe Accessors

```php
// Instead of this everywhere:
$debug = (bool) Config::get('app.debug', false);

// Create a helper:
class Cfg
{
    public static function debug(): bool
    {
        return (bool) Config::get('app.debug', false);
    }
    
    public static function url(): string
    {
        return Config::get('app.url');
    }
}

// Use:
if (Cfg::debug()) {
    // ...
}
```

## Common Patterns

### Feature Flags

```php
// config/features.php
return [
    'features' => [
        'new_dashboard' => true,
        'beta_api' => false,
        'social_login' => true,
    ],
];

// Helper
class Feature
{
    public static function enabled(string $feature): bool
    {
        return (bool) Config::get("features.{$feature}", false);
    }
}

// Usage
if (Feature::enabled('new_dashboard')) {
    // Show new dashboard
}
```

### Multi-Tenant Configuration

```php
// config/tenants.php
return [
    'tenants' => [
        'tenant1' => [
            'database' => 'tenant1_db',
            'domain' => 'tenant1.example.com',
        ],
        'tenant2' => [
            'database' => 'tenant2_db',
            'domain' => 'tenant2.example.com',
        ],
    ],
];

// Resolve tenant from request
$host = $_SERVER['HTTP_HOST'];
$tenant = Config::get("tenants.{$tenantId}");
```

### Service URLs

```php
return [
    'services' => [
        'api' => [
            'base_url' => '@{app.url}/api/v1',
            'timeout' => 30,
        ],
        'cdn' => [
            'url' => 'https://cdn.example.com',
            'assets' => '@{services.cdn.url}/assets',
        ],
    ],
];
```

## Troubleshooting

### Issue: Configuration Not Loading

**Check:**
1. File path is correct
2. Config file returns an array
3. File has `.php` extension
4. Syntax errors in config file

### Issue: Reference Not Resolving

**Solution:** Ensure referenced key exists before the reference:
```php
// This works:
return [
    'app' => ['url' => 'https://example.com'],
    'api' => ['url' => '@{app.url}/api'],
];

// This doesn't:
return [
    'api' => ['url' => '@{app.url}/api'],
    'app' => ['url' => 'https://example.com'], // Too late!
];
```

### Issue: Environment Variables Not Available

**Solution:** Load environment variables before loading config:
```php
// Load .env first
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Then load config
Config::load(__DIR__ . '/config/*.php');
```

## Related Documentation

- [Getting Started](./getting-started.md) - Basic configuration setup
- [Architecture Overview](./architecture.md) - How config fits in
- [Model & Database](./model.md) - Database configuration
- [CLI Commands](./cli.md) - CLI configuration

---

[← Back to Documentation Index](./README.md) | [Getting Started →](./getting-started.md)
