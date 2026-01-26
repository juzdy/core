# Event System

JUZDY Core implements a full PSR-14 compliant event system for building event-driven applications with loose coupling between components.

## Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Event Classes](#event-classes)
- [Event Dispatcher](#event-dispatcher)
- [Listener Provider](#listener-provider)
- [Built-in Events](#built-in-events)
- [Custom Events](#custom-events)
- [Best Practices](#best-practices)

## Overview

The event system provides:
- **PSR-14 compliance** - Standard event dispatcher interface
- **Event propagation control** - Stop propagation when needed
- **Context-based events** - Attach data to events
- **Immutable events** - Events are cloned when modified
- **Listener registry** - Centralized listener management

### Key Components

```
EventDispatcher → ListenerProvider → Event Listeners
        ↓
   Event Object (with context)
```

## Basic Usage

### Dispatching Events

```php
use Psr\EventDispatcher\EventDispatcherInterface;
use Juzdy\EventBus\Event\Event;

class UserCreateHandler
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {
    }
    
    public function handle(array $userData): User
    {
        // Create event
        $event = new Event();
        $event->attach('userData', $userData);
        
        // Dispatch event - listeners can modify userData
        $event = $this->dispatcher->dispatch($event);
        
        // Get possibly modified data
        $userData = $event->get('userData');
        
        // Create user with potentially modified data
        $user = User::create($userData);
        
        return $user;
    }
}
```

### Listening to Events

```php
use Juzdy\EventBus\ListenerProviderInterface;

class Bootstrap
{
    public function __construct(
        private ListenerProviderInterface $listenerProvider
    ) {
    }
    
    public function boot(): void
    {
        // Register listener
        $this->listenerProvider->addListener(
            UserCreatedEvent::class,
            function(UserCreatedEvent $event) {
                $user = $event->getUser();
                // Send welcome email, log, etc.
            }
        );
    }
}
```

## Event Classes

### Base Event Class

All events extend the base `Event` class:

```php
use Juzdy\EventBus\Event\Event;
use Juzdy\EventBus\Event\EventInterface;

class Event implements EventInterface
{
    private array $context = [];
    private bool $propagationStopped = false;
    
    // Attach data to event
    public function attach(string $key, mixed $value): static;
    
    // Get attached data
    public function get(string $key): mixed;
    
    // Check if event has key
    public function has(string $key): bool;
    
    // Get all context
    public function getContext(): array;
    
    // Stop event propagation
    public function stopPropagation(): void;
    
    // Check if propagation stopped
    public function isPropagationStopped(): bool;
    
    // Create new event with additional context
    public function with(array $context): static;
}
```

### Event Context

Events carry context data:

```php
$event = new Event();

// Attach data
$event->attach('user', $user);
$event->attach('timestamp', time());

// Retrieve data
$user = $event->get('user');
$timestamp = $event->get('timestamp');

// Check existence
if ($event->has('user')) {
    // ...
}

// Get all context
$context = $event->getContext();
// ['user' => $user, 'timestamp' => $timestamp]
```

### Immutable Events

Events are immutable when using `with()`:

```php
$event1 = new Event();
$event1->attach('key', 'value1');

// Creates new event instance
$event2 = $event1->with(['key' => 'value2']);

// Original unchanged
echo $event1->get('key'); // 'value1'
echo $event2->get('key'); // 'value2'
```

## Event Dispatcher

### EventDispatcher Implementation

```php
use Juzdy\EventBus\EventDispatcher;
use Juzdy\EventBus\ListenerProvider;

$listenerProvider = new ListenerProvider();
$dispatcher = new EventDispatcher($listenerProvider);

// Dispatch event
$event = new UserCreatedEvent($user);
$dispatchedEvent = $dispatcher->dispatch($event);
```

### Getting the Dispatcher

The dispatcher is available via dependency injection:

```php
use Psr\EventDispatcher\EventDispatcherInterface;

class MyService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {
    }
    
    public function doSomething(): void
    {
        $event = new SomethingHappenedEvent();
        $this->dispatcher->dispatch($event);
    }
}
```

## Listener Provider

### Registering Listeners

The `ListenerProvider` manages event listeners:

```php
use Juzdy\EventBus\ListenerProvider;

$provider = new ListenerProvider();

// Add listener with callable
$provider->addListener(
    UserCreatedEvent::class,
    function(UserCreatedEvent $event) {
        // Handle event
        $user = $event->getUser();
        $this->sendWelcomeEmail($user);
    }
);

// Add multiple listeners for same event
$provider->addListener(
    UserCreatedEvent::class,
    [$logger, 'logUserCreation']
);

$provider->addListener(
    UserCreatedEvent::class,
    [$analytics, 'trackUserSignup']
);
```

### Listener Execution Order

Listeners execute in the order they were registered:

```php
$provider->addListener(SomeEvent::class, $listener1); // Executes first
$provider->addListener(SomeEvent::class, $listener2); // Executes second
$provider->addListener(SomeEvent::class, $listener3); // Executes third
```

### Getting Listeners

```php
$listeners = $provider->getListenersForEvent($event);

foreach ($listeners as $listener) {
    $listener($event);
}
```

## Built-in Events

### HTTP Events

#### BeforeRun Event

Dispatched before the HTTP application starts:

```php
use Juzdy\Http\Event\BeforeRun;

$listenerProvider->addListener(
    BeforeRun::class,
    function(BeforeRun $event) {
        $app = $event->get('app');
        // Initialize session, check maintenance mode, etc.
    }
);
```

### Application Events

Application-level events for lifecycle management.

## Custom Events

### Creating Custom Events

```php
use Juzdy\EventBus\Event\Event;

class UserCreatedEvent extends Event
{
    public function __construct(
        private User $user
    ) {
        parent::__construct();
        $this->attach('user', $user);
    }
    
    public function getUser(): User
    {
        return $this->user;
    }
}
```

### Type-Safe Events

For better IDE support and type safety:

```php
class OrderPlacedEvent extends Event
{
    public function __construct(
        private Order $order,
        private Customer $customer
    ) {
        parent::__construct();
    }
    
    public function getOrder(): Order
    {
        return $this->order;
    }
    
    public function getCustomer(): Customer
    {
        return $this->customer;
    }
}

// Usage
$event = new OrderPlacedEvent($order, $customer);
$this->dispatcher->dispatch($event);

// In listener
$listenerProvider->addListener(
    OrderPlacedEvent::class,
    function(OrderPlacedEvent $event) {
        $order = $event->getOrder();      // Type-safe
        $customer = $event->getCustomer(); // Type-safe
        
        $this->sendConfirmationEmail($order, $customer);
    }
);
```

### Mutable Event Data

Allow listeners to modify event data:

```php
class DataProcessingEvent extends Event
{
    public function __construct(
        private array $data
    ) {
        parent::__construct();
    }
    
    public function getData(): array
    {
        return $this->data;
    }
    
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}

// Dispatching
$event = new DataProcessingEvent(['name' => 'John']);
$event = $this->dispatcher->dispatch($event);
$processedData = $event->getData(); // May be modified by listeners

// Listener can modify
$listenerProvider->addListener(
    DataProcessingEvent::class,
    function(DataProcessingEvent $event) {
        $data = $event->getData();
        $data['processed'] = true;
        $data['timestamp'] = time();
        $event->setData($data);
    }
);
```

## Event Propagation

### Stopping Propagation

Prevent subsequent listeners from executing:

```php
$listenerProvider->addListener(
    ValidationEvent::class,
    function(ValidationEvent $event) {
        if (!$this->validate($event->getData())) {
            $event->stopPropagation();
            $event->attach('error', 'Validation failed');
        }
    }
);

// This listener won't execute if propagation was stopped
$listenerProvider->addListener(
    ValidationEvent::class,
    function(ValidationEvent $event) {
        // Only runs if validation passed
        $this->processData($event->getData());
    }
);

// Check if propagation stopped
$event = $dispatcher->dispatch($event);
if ($event->isPropagationStopped()) {
    $error = $event->get('error');
    // Handle error
}
```

### Conditional Listeners

Execute listeners based on conditions:

```php
$listenerProvider->addListener(
    UserEvent::class,
    function(UserEvent $event) {
        $user = $event->getUser();
        
        // Only process for premium users
        if (!$user->isPremium()) {
            return;
        }
        
        $this->sendPremiumNotification($user);
    }
);
```

## Common Patterns

### Audit Logging

```php
class AuditLogListener
{
    public function __construct(
        private LoggerInterface $logger,
        private Database $db
    ) {
    }
    
    public function __invoke(AuditableEvent $event): void
    {
        $this->db->insert('audit_log', [
            'event_type' => get_class($event),
            'user_id' => $event->getUserId(),
            'data' => json_encode($event->getContext()),
            'timestamp' => time(),
        ]);
    }
}

// Register
$listenerProvider->addListener(
    UserCreatedEvent::class,
    new AuditLogListener($logger, $db)
);
$listenerProvider->addListener(
    UserUpdatedEvent::class,
    new AuditLogListener($logger, $db)
);
```

### Email Notifications

```php
class EmailNotificationListener
{
    public function __construct(
        private MailerInterface $mailer
    ) {
    }
    
    public function onUserCreated(UserCreatedEvent $event): void
    {
        $user = $event->getUser();
        
        $this->mailer->send(
            $user->getEmail(),
            'Welcome!',
            'welcome_email.phtml',
            ['user' => $user]
        );
    }
}

$listenerProvider->addListener(
    UserCreatedEvent::class,
    [$listener, 'onUserCreated']
);
```

### Cache Invalidation

```php
class CacheInvalidationListener
{
    public function __construct(
        private CacheInterface $cache
    ) {
    }
    
    public function __invoke(EntityChangedEvent $event): void
    {
        $entity = $event->getEntity();
        
        // Invalidate related caches
        $this->cache->delete("entity.{$entity->getId()}");
        $this->cache->delete("entity.list");
    }
}

$listenerProvider->addListener(
    UserUpdatedEvent::class,
    new CacheInvalidationListener($cache)
);
$listenerProvider->addListener(
    UserDeletedEvent::class,
    new CacheInvalidationListener($cache)
);
```

### Event Sourcing

```php
class EventStoreListener
{
    public function __construct(
        private EventStore $eventStore
    ) {
    }
    
    public function __invoke(DomainEvent $event): void
    {
        $this->eventStore->append([
            'event_type' => get_class($event),
            'aggregate_id' => $event->getAggregateId(),
            'payload' => $event->getContext(),
            'occurred_at' => $event->getOccurredAt(),
        ]);
    }
}
```

## Best Practices

### 1. Use Descriptive Event Names

✅ **Good:**
```php
class UserAccountActivatedEvent extends Event { }
class OrderPaymentCompletedEvent extends Event { }
```

❌ **Avoid:**
```php
class UserEvent extends Event { }
class Event1 extends Event { }
```

### 2. Make Events Immutable

Events should not change after dispatch:

```php
class UserCreatedEvent extends Event
{
    public function __construct(
        private readonly User $user  // readonly
    ) {
    }
    
    public function getUser(): User
    {
        return $this->user;
    }
}
```

### 3. Include Relevant Context

Provide all data listeners might need:

```php
class OrderPlacedEvent extends Event
{
    public function __construct(
        private Order $order,
        private Customer $customer,
        private PaymentMethod $payment,
        private ShippingAddress $address
    ) {
    }
}
```

### 4. Don't Depend on Listener Execution Order

Each listener should be independent:

```php
// ❌ Don't assume listener1 runs before listener2
$provider->addListener(Event::class, $listener1);
$provider->addListener(Event::class, $listener2); // Don't depend on listener1's changes
```

### 5. Handle Listener Failures

```php
$listenerProvider->addListener(
    SomeEvent::class,
    function(SomeEvent $event) {
        try {
            $this->doSomething($event);
        } catch (Exception $e) {
            $this->logger->error('Listener failed', [
                'event' => get_class($event),
                'exception' => $e
            ]);
            // Don't rethrow - let other listeners run
        }
    }
);
```

### 6. Use Type-Safe Event Classes

Prefer specific event classes over generic events:

✅ **Good:**
```php
class UserCreatedEvent extends Event
{
    public function getUser(): User { }
}
```

❌ **Avoid:**
```php
$event = new Event();
$event->attach('user', $user);  // No type safety
```

### 7. Document Event Contracts

Document what data events contain:

```php
/**
 * Dispatched when a new user registers
 * 
 * Context:
 * - 'user' (User): The newly created user
 * - 'source' (string): Registration source (web, mobile, api)
 */
class UserRegisteredEvent extends Event
{
}
```

## Troubleshooting

### Issue: Listener Not Executing

**Check:**
1. Is the listener registered? `$provider->addListener(...)`
2. Is the correct event class dispatched?
3. Is propagation stopped by an earlier listener?

### Issue: Event Data Not Available

**Solution:** Ensure data is attached before dispatch:
```php
$event = new Event();
$event->attach('data', $data);  // Attach before dispatch
$this->dispatcher->dispatch($event);
```

### Issue: Circular Event Dispatch

**Problem:** Event A triggers listener that dispatches Event A again.

**Solution:** Add guard conditions:
```php
private bool $processing = false;

public function onEvent(Event $event): void
{
    if ($this->processing) {
        return;
    }
    
    $this->processing = true;
    try {
        // Process event
    } finally {
        $this->processing = false;
    }
}
```

## Related Documentation

- [Architecture Overview](./architecture.md) - Framework architecture
- [HTTP Handling](./http.md) - HTTP events
- [Model & Database](./model.md) - Model lifecycle events
- [Dependency Injection](./container.md) - Injecting event dispatcher

---

[← Back to Documentation Index](./README.md) | [Next: Model & Database →](./model.md)
