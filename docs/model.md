# Model & Database

JUZDY Core provides a lightweight ORM with an Active Record pattern for database interactions, featuring lifecycle hooks and automatic field validation.

## Table of Contents

- [Overview](#overview)
- [Database Connection](#database-connection)
- [Model Basics](#model-basics)
- [CRUD Operations](#crud-operations)
- [Lifecycle Hooks](#lifecycle-hooks)
- [Collections](#collections)
- [Advanced Usage](#advanced-usage)
- [Best Practices](#best-practices)

## Overview

The Model layer provides:
- **Active Record pattern** - Models handle their own persistence
- **PDO-based** - Reliable database connection through PDO
- **Lifecycle hooks** - Before/after callbacks for all operations
- **Field validation** - Automatic field existence checking
- **Collections** - Work with multiple records
- **JSON encoding** - Automatic array/object to JSON conversion

## Database Connection

### Configuration

Configure database in your config file:

```php
// config/database.php
return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'myapp',
        'username' => 'root',
        'password' => 'secret',
        'charset' => 'utf8mb4',
    ],
];
```

### Establishing Connection

```php
use Juzdy\Database;

// Get PDO instance
$pdo = Database::connect();

// Execute raw queries
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
```

### Connection Pooling

The Database class manages connection pooling automatically:

```php
// Subsequent calls return the same PDO instance
$pdo1 = Database::connect();
$pdo2 = Database::connect();
// $pdo1 === $pdo2
```

## Model Basics

### Creating a Model

```php
<?php
namespace App\Model;

use Juzdy\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    // Define fields (optional but recommended)
    protected array $fields = [
        'id',
        'name',
        'email',
        'password',
        'created_at',
        'updated_at',
    ];
}
```

### Model Properties

```php
class Product extends Model
{
    // Table name (required)
    protected string $table = 'products';
    
    // Primary key column (default: 'id')
    protected string $primaryKey = 'id';
    
    // Fields list (optional)
    protected array $fields = ['id', 'name', 'price', 'stock'];
    
    // Use custom collection class
    protected string $collectionClass = ProductCollection::class;
}
```

### Setting and Getting Data

```php
$user = new User();

// Set fields
$user->set('name', 'John Doe');
$user->set('email', 'john@example.com');

// Get fields
$name = $user->get('name');
$email = $user->get('email');

// Get all data as array
$data = $user->toArray();
// ['name' => 'John Doe', 'email' => 'john@example.com']

// Check if field exists
if ($user->has('email')) {
    // Field is set
}

// Array access (implements ArrayAccess)
$user['name'] = 'Jane Doe';
$name = $user['name'];

// Invoke to get data
$allData = $user();
```

## CRUD Operations

### Create (Insert)

```php
$user = new User();
$user->set('name', 'John Doe');
$user->set('email', 'john@example.com');
$user->set('password', password_hash('secret', PASSWORD_DEFAULT));

// Insert into database
$user->save();  // Calls create() internally

// Or explicitly create
$user->create();

// Get generated ID
$userId = $user->get('id');
```

### Read (Select)

```php
// Find by primary key
$user = User::find(1);  // Static factory method
if ($user) {
    echo $user->get('name');
}

// Load by ID (instance method)
$user = new User();
$user->load(1);

// Load by custom condition
$user = new User();
$user->loadBy('email', 'john@example.com');

// Get all records
$user = new User();
$allUsers = $user->allAsArray();
// Returns array of associative arrays

// Get all as collection
$users = $user->all();
// Returns Collection object
```

### Update

```php
// Load existing record
$user = User::find(1);

// Modify fields
$user->set('name', 'Jane Doe');
$user->set('email', 'jane@example.com');

// Update database
$user->save();  // Calls update() internally

// Or explicitly update
$user->update();
```

### Delete

```php
// Load and delete
$user = User::find(1);
$user->delete();

// Check if deleted
if (!$user->get('id')) {
    echo "User deleted";
}
```

### Save Method

The `save()` method intelligently chooses between insert and update:

```php
$user = new User();
$user->set('name', 'John');
$user->save();  // INSERT (no ID present)

$user->set('email', 'john@example.com');
$user->save();  // UPDATE (ID present from previous save)
```

## Lifecycle Hooks

Models provide before/after hooks for all operations:

### Available Hooks

```php
class User extends Model
{
    // Called before/after loading from database
    protected function _beforeLoad(): void { }
    protected function _afterLoad(): void { }
    
    // Called before/after any save (create or update)
    protected function _beforeSave(): void { }
    protected function _afterSave(): void { }
    
    // Called before/after insert
    protected function _beforeCreate(): void { }
    protected function _afterCreate(): void { }
    
    // Called before/after update
    protected function _beforeUpdate(): void { }
    protected function _afterUpdate(): void { }
    
    // Called before/after delete
    protected function _beforeDelete(): void { }
    protected function _afterDelete(): void { }
}
```

### Hook Examples

#### Timestamps

```php
class User extends Model
{
    protected function _beforeCreate(): void
    {
        $this->set('created_at', date('Y-m-d H:i:s'));
        $this->set('updated_at', date('Y-m-d H:i:s'));
    }
    
    protected function _beforeUpdate(): void
    {
        $this->set('updated_at', date('Y-m-d H:i:s'));
    }
}
```

#### Password Hashing

```php
class User extends Model
{
    protected function _beforeSave(): void
    {
        // Hash password if it's been set and not already hashed
        if ($this->has('password') && !$this->isPasswordHashed()) {
            $password = $this->get('password');
            $this->set('password', password_hash($password, PASSWORD_DEFAULT));
        }
    }
    
    private function isPasswordHashed(): bool
    {
        $password = $this->get('password');
        return str_starts_with($password, '$2y$');
    }
}
```

#### Validation

```php
class User extends Model
{
    protected function _beforeSave(): void
    {
        $this->validateEmail();
        $this->validatePasswordStrength();
    }
    
    private function validateEmail(): void
    {
        $email = $this->get('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email address');
        }
    }
    
    private function validatePasswordStrength(): void
    {
        if ($this->has('password')) {
            $password = $this->get('password');
            if (strlen($password) < 8) {
                throw new ValidationException('Password must be at least 8 characters');
            }
        }
    }
}
```

#### Soft Deletes

```php
class User extends Model
{
    protected function _beforeDelete(): void
    {
        // Instead of deleting, mark as deleted
        $this->set('deleted_at', date('Y-m-d H:i:s'));
        $this->update();
        
        // Prevent actual deletion
        throw new CancelOperationException();
    }
}
```

#### Logging

```php
class User extends Model
{
    public function __construct(
        private ?LoggerInterface $logger = null
    ) {
        parent::__construct();
    }
    
    protected function _afterCreate(): void
    {
        $this->logger?->info('User created', [
            'user_id' => $this->get('id'),
            'email' => $this->get('email'),
        ]);
    }
    
    protected function _afterDelete(): void
    {
        $this->logger?->info('User deleted', [
            'user_id' => $this->get('id'),
        ]);
    }
}
```

## Collections

### Collection Basics

Collections are returned when fetching multiple records:

```php
$user = new User();
$users = $user->all();  // Returns Collection

// Iterate
foreach ($users as $user) {
    echo $user->get('name');
}

// Count
$count = count($users);

// Array access
$firstUser = $users[0];

// Convert to array
$usersArray = $users->toArray();
```

### Custom Collections

Create custom collection classes with additional methods:

```php
use Juzdy\Model\Collection;

class UserCollection extends Collection
{
    public function getActiveUsers(): self
    {
        return $this->filter(function($user) {
            return $user->get('status') === 'active';
        });
    }
    
    public function getTotalRevenue(): float
    {
        return $this->sum(function($user) {
            return $user->get('lifetime_value');
        });
    }
}

// Use in model
class User extends Model
{
    protected string $collectionClass = UserCollection::class;
}

// Usage
$users = $user->all();
$active = $users->getActiveUsers();
$revenue = $users->getTotalRevenue();
```

## Advanced Usage

### Raw Queries

```php
class User extends Model
{
    public function findByEmailDomain(string $domain): array
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "SELECT * FROM {$this->table} WHERE email LIKE ?"
        );
        $stmt->execute(["%@{$domain}"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$user = new User();
$gmailUsers = $user->findByEmailDomain('gmail.com');
```

### Relationships (Manual)

JUZDY Core doesn't have built-in relationship support, but you can implement them:

```php
class User extends Model
{
    public function posts(): Collection
    {
        $post = new Post();
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "SELECT * FROM posts WHERE user_id = ?"
        );
        $stmt->execute([$this->get('id')]);
        
        $posts = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $postModel = new Post();
            foreach ($row as $key => $value) {
                $postModel->set($key, $value);
            }
            $posts[] = $postModel;
        }
        
        return new Collection($posts);
    }
}

// Usage
$user = User::find(1);
$posts = $user->posts();
```

### JSON Fields

Automatically encode/decode JSON:

```php
class User extends Model
{
    protected function _beforeSave(): void
    {
        // Encode array/object fields to JSON
        if ($this->has('preferences') && is_array($this->get('preferences'))) {
            $this->set('preferences', json_encode($this->get('preferences')));
        }
    }
    
    protected function _afterLoad(): void
    {
        // Decode JSON to array
        if ($this->has('preferences') && is_string($this->get('preferences'))) {
            $this->set('preferences', json_decode($this->get('preferences'), true));
        }
    }
}

// Usage
$user = User::find(1);
$prefs = $user->get('preferences');  // Array, not JSON string
```

### Scopes

Create reusable query methods:

```php
class User extends Model
{
    public function scopeActive(): self
    {
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "SELECT * FROM {$this->table} WHERE status = 'active'"
        );
        $stmt->execute();
        
        // Load first result into this instance
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            foreach ($row as $key => $value) {
                $this->set($key, $value);
            }
        }
        
        return $this;
    }
}

// Usage
$user = (new User())->scopeActive();
```

## Best Practices

### 1. Always Use Prepared Statements

✅ **Good:**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

❌ **Avoid (SQL Injection Risk):**
```php
$stmt = $pdo->query("SELECT * FROM users WHERE email = '$email'");
```

### 2. Use Lifecycle Hooks for Business Logic

Keep data integrity logic in model hooks:

```php
protected function _beforeSave(): void
{
    $this->validateData();
    $this->sanitizeData();
    $this->setTimestamps();
}
```

### 3. Define Fields Explicitly

This helps catch typos and improves code clarity:

```php
class User extends Model
{
    protected array $fields = [
        'id', 'name', 'email', 'password', 'created_at', 'updated_at'
    ];
}
```

### 4. Use Static Factory Method

```php
// ✅ Good - More readable
$user = User::find(1);

// ✅ Also good
$user = new User();
$user->load(1);
```

### 5. Handle Validation in Models

```php
class User extends Model
{
    protected function _beforeSave(): void
    {
        if (!$this->isValid()) {
            throw new ValidationException($this->getErrors());
        }
    }
    
    private function isValid(): bool
    {
        return $this->validateEmail() 
            && $this->validatePassword();
    }
}
```

### 6. Don't Store Sensitive Data in Plain Text

```php
protected function _beforeSave(): void
{
    if ($this->has('password')) {
        $this->set('password', password_hash(
            $this->get('password'),
            PASSWORD_DEFAULT
        ));
    }
    
    if ($this->has('ssn')) {
        $this->set('ssn', $this->encrypt($this->get('ssn')));
    }
}
```

### 7. Use Transactions for Multiple Operations

```php
class OrderService
{
    public function createOrder(array $orderData): Order
    {
        $pdo = Database::connect();
        $pdo->beginTransaction();
        
        try {
            $order = new Order();
            $order->fill($orderData);
            $order->save();
            
            foreach ($orderData['items'] as $item) {
                $orderItem = new OrderItem();
                $orderItem->set('order_id', $order->get('id'));
                $orderItem->fill($item);
                $orderItem->save();
            }
            
            $pdo->commit();
            return $order;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
```

## Common Patterns

### Repository Pattern

```php
class UserRepository
{
    public function __construct(
        private Database $db
    ) {
    }
    
    public function find(int $id): ?User
    {
        return User::find($id);
    }
    
    public function findByEmail(string $email): ?User
    {
        $user = new User();
        return $user->loadBy('email', $email);
    }
    
    public function findActive(): Collection
    {
        $user = new User();
        $pdo = Database::connect();
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE status = 'active'"
        );
        $stmt->execute();
        
        // Build collection...
    }
    
    public function create(array $data): User
    {
        $user = new User();
        $user->fill($data);
        $user->save();
        return $user;
    }
}
```

### Value Objects in Models

```php
class User extends Model
{
    public function getEmail(): Email
    {
        return new Email($this->get('email'));
    }
    
    public function setEmail(Email $email): void
    {
        $this->set('email', $email->getValue());
    }
}

// Value Object
class Email
{
    public function __construct(private string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }
    }
    
    public function getValue(): string
    {
        return $this->email;
    }
}
```

## Troubleshooting

### Issue: Field Not Found

**Solution:** Add field to `$fields` array or disable field checking.

### Issue: Primary Key Not Set After Insert

**Solution:** Ensure your database returns the last insert ID and PDO is configured correctly.

### Issue: JSON Data Not Encoding

**Solution:** Implement _beforeSave hook to encode arrays to JSON.

## Related Documentation

- [Architecture Overview](./architecture.md) - Framework architecture
- [HTTP Handling](./http.md) - Using models in handlers
- [Events](./events.md) - Model lifecycle events
- [CLI Commands](./cli.md) - Database migrations

---

[← Back to Documentation Index](./README.md) | [Next: Layout & Views →](./layout.md)
