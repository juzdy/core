# Layout & Views

JUZDY Core provides a flexible layout and view system with PHP templates, context management, and asset handling.

## Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Context Management](#context-management)
- [Templates](#templates)
- [Asset Management](#asset-management)
- [Layouts](#layouts)
- [Best Practices](#best-practices)

## Overview

The Layout system provides:
- **PHP templates** - Simple `.phtml` files
- **Context variables** - Pass data to templates
- **Asset management** - CSS/JS queueing and rendering
- **Layout nesting** - Master layouts with content blocks
- **View rendering** - Render templates from handlers

## Basic Usage

### Rendering in Handlers

```php
use Juzdy\Http\Handler;
use Juzdy\Http\Handler\RenderTrait;

class PageHandler extends Handler
{
    use RenderTrait;
    
    public function handle(RequestInterface $request): ResponseInterface
    {
        // Get layout instance
        $layout = $this->getLayout();
        
        // Set context variables
        $layout->context('title', 'Welcome Page');
        $layout->context('user', $this->getCurrentUser());
        
        // Render template
        return $this->render('page/welcome.phtml');
    }
}
```

### Simple Template

```php
// templates/page/welcome.phtml
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->escape($title) ?></title>
</head>
<body>
    <h1>Welcome, <?= $this->escape($user->getName()) ?>!</h1>
</body>
</html>
```

## Context Management

### Setting Context Variables

```php
$layout = $this->getLayout();

// Single variable
$layout->context('title', 'My Page');

// Multiple variables
$layout->context([
    'title' => 'My Page',
    'description' => 'Page description',
    'keywords' => ['php', 'framework'],
]);

// Object/array data
$layout->context('user', $user);
$layout->context('posts', $posts);
```

### Accessing Context in Templates

```php
// In template.phtml

// Simple variables
<?= $title ?>

// Object properties
<?= $user->getName() ?>
<?= $user->getEmail() ?>

// Arrays
<?php foreach ($posts as $post): ?>
    <article>
        <h2><?= $post['title'] ?></h2>
        <p><?= $post['content'] ?></p>
    </article>
<?php endforeach; ?>

// Check if variable exists
<?php if (isset($user)): ?>
    <p>Welcome, <?= $user->getName() ?></p>
<?php endif; ?>
```

### Escaping Output

Always escape user-provided content:

```php
// Escape HTML
<?= $this->escape($userInput) ?>

// Or use htmlspecialchars directly
<?= htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8') ?>

// Safe for attributes
<input type="text" value="<?= $this->escape($value) ?>">

// Safe for JavaScript strings
<script>
var username = <?= json_encode($username) ?>;
</script>
```

## Templates

### Template Structure

```
templates/
├── layout/
│   ├── default.phtml       # Main layout
│   ├── admin.phtml         # Admin layout
│   └── blank.phtml         # Minimal layout
├── page/
│   ├── home.phtml
│   ├── about.phtml
│   └── contact.phtml
├── user/
│   ├── profile.phtml
│   └── settings.phtml
└── partials/
    ├── header.phtml
    ├── footer.phtml
    └── sidebar.phtml
```

### Creating Templates

```php
// templates/page/profile.phtml
<div class="profile">
    <div class="profile-header">
        <img src="<?= $user->getAvatar() ?>" alt="Avatar">
        <h1><?= $this->escape($user->getName()) ?></h1>
    </div>
    
    <div class="profile-bio">
        <?= nl2br($this->escape($user->getBio())) ?>
    </div>
    
    <div class="profile-stats">
        <div class="stat">
            <span class="label">Posts</span>
            <span class="value"><?= $user->getPostCount() ?></span>
        </div>
        <div class="stat">
            <span class="label">Followers</span>
            <span class="value"><?= $user->getFollowerCount() ?></span>
        </div>
    </div>
</div>
```

### Including Partials

```php
// templates/page/home.phtml
<?php $this->render('partials/header.phtml') ?>

<main>
    <h1><?= $title ?></h1>
    <p><?= $content ?></p>
</main>

<?php $this->render('partials/footer.phtml') ?>
```

## Asset Management

### Adding Assets

```php
use Juzdy\Layout\Asset\Asset;

$layout = $this->getLayout();

// Add CSS
$layout->addCss('/css/main.css');
$layout->addCss('/css/custom.css', ['media' => 'screen']);

// Add JavaScript
$layout->addJs('/js/app.js');
$layout->addJs('/js/analytics.js', ['async' => true]);

// Add external assets
$layout->addCss('https://cdn.example.com/bootstrap.css');
$layout->addJs('https://cdn.example.com/jquery.js');
```

### Rendering Assets in Templates

```php
// templates/layout/default.phtml
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $this->escape($title ?? 'My App') ?></title>
    
    <!-- Render all CSS -->
    <?= $this->renderCss() ?>
</head>
<body>
    <?= $content ?>
    
    <!-- Render all JavaScript -->
    <?= $this->renderJs() ?>
</body>
</html>
```

### Asset Attributes

```php
// CSS with media query
$layout->addCss('/css/print.css', [
    'media' => 'print'
]);

// JavaScript with defer
$layout->addJs('/js/app.js', [
    'defer' => true
]);

// JavaScript with async
$layout->addJs('/js/analytics.js', [
    'async' => true
]);

// With integrity hash
$layout->addJs('https://cdn.example.com/lib.js', [
    'integrity' => 'sha384-...',
    'crossorigin' => 'anonymous'
]);
```

### Inline Styles and Scripts

```php
// Inline CSS
$layout->addInlineCss('
    .custom-style {
        color: red;
    }
');

// Inline JavaScript
$layout->addInlineJs('
    console.log("Page loaded");
    initApp();
');
```

## Layouts

### Master Layout

```php
// templates/layout/default.phtml
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->escape($title ?? 'My Application') ?></title>
    
    <?= $this->renderCss() ?>
</head>
<body>
    <?php $this->render('partials/header.phtml') ?>
    
    <main class="container">
        <?= $content ?>
    </main>
    
    <?php $this->render('partials/footer.phtml') ?>
    
    <?= $this->renderJs() ?>
</body>
</html>
```

### Using Layouts

```php
class PageHandler extends Handler
{
    use RenderTrait;
    
    public function handle(RequestInterface $request): ResponseInterface
    {
        $layout = $this->getLayout();
        
        // Set layout template
        $layout->setLayout('layout/default.phtml');
        
        // Set context
        $layout->context('title', 'My Page');
        $layout->context('content', $this->renderContent());
        
        // Render with layout
        return $this->render();
    }
    
    private function renderContent(): string
    {
        $layout = $this->getLayout();
        $layout->context('data', $this->getData());
        return $layout->render('page/content.phtml');
    }
}
```

### Multiple Layouts

```php
// Admin pages use admin layout
class AdminHandler extends Handler
{
    use RenderTrait;
    
    protected function getDefaultLayout(): string
    {
        return 'layout/admin.phtml';
    }
}

// Public pages use default layout
class PublicHandler extends Handler
{
    use RenderTrait;
    
    protected function getDefaultLayout(): string
    {
        return 'layout/default.phtml';
    }
}
```

## Advanced Features

### View Helpers

Create reusable template helpers:

```php
class ViewHelper
{
    public function formatDate(string $date): string
    {
        return date('F j, Y', strtotime($date));
    }
    
    public function truncate(string $text, int $length = 100): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
    
    public function url(string $path): string
    {
        return Config::get('app.base_url') . $path;
    }
}

// In handler
$layout->context('helper', new ViewHelper());

// In template
<?= $helper->formatDate($post->created_at) ?>
<?= $helper->truncate($post->content, 200) ?>
<a href="<?= $helper->url('/contact') ?>">Contact</a>
```

### Template Inheritance

```php
// templates/layout/base.phtml
<!DOCTYPE html>
<html>
<head>
    <title><?= $this->getTitle() ?></title>
    <?= $this->renderCss() ?>
</head>
<body>
    <header>
        <?= $this->renderBlock('header') ?>
    </header>
    
    <main>
        <?= $this->renderBlock('content') ?>
    </main>
    
    <footer>
        <?= $this->renderBlock('footer') ?>
    </footer>
    
    <?= $this->renderJs() ?>
</body>
</html>

// templates/page/article.phtml extending base
<?php $this->setBlock('header') ?>
    <h1>Article Title</h1>
<?php $this->endBlock() ?>

<?php $this->setBlock('content') ?>
    <article>
        <?= $article->getContent() ?>
    </article>
<?php $this->endBlock() ?>

<?php $this->setBlock('footer') ?>
    <p>Published on <?= $article->getDate() ?></p>
<?php $this->endBlock() ?>
```

### Conditional Rendering

```php
// In template
<?php if ($user->isAdmin()): ?>
    <div class="admin-panel">
        <a href="/admin">Admin Panel</a>
    </div>
<?php endif; ?>

<?php if (!empty($messages)): ?>
    <div class="messages">
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-<?= $message['type'] ?>">
                <?= $this->escape($message['text']) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

## Best Practices

### 1. Always Escape Output

✅ **Good:**
```php
<?= $this->escape($userInput) ?>
<?= htmlspecialchars($data, ENT_QUOTES, 'UTF-8') ?>
```

❌ **Dangerous:**
```php
<?= $userInput ?>  // XSS vulnerability
```

### 2. Keep Logic Out of Templates

✅ **Good:**
```php
// In handler
$layout->context('users', $this->getActiveUsers());

// In template
<?php foreach ($users as $user): ?>
    <li><?= $user->getName() ?></li>
<?php endforeach; ?>
```

❌ **Avoid:**
```php
// In template - too much logic
<?php
$pdo = Database::connect();
$stmt = $pdo->query("SELECT * FROM users WHERE active = 1");
$users = $stmt->fetchAll();
foreach ($users as $user):
?>
    <li><?= $user['name'] ?></li>
<?php endforeach; ?>
```

### 3. Use Partials for Reusable Components

```php
// partials/user-card.phtml
<div class="user-card">
    <img src="<?= $user->getAvatar() ?>">
    <h3><?= $this->escape($user->getName()) ?></h3>
    <p><?= $this->escape($user->getBio()) ?></p>
</div>

// In main template
<?php foreach ($users as $user): ?>
    <?php $this->render('partials/user-card.phtml', ['user' => $user]) ?>
<?php endforeach; ?>
```

### 4. Organize Templates by Feature

```
templates/
├── user/
│   ├── profile.phtml
│   ├── edit.phtml
│   └── list.phtml
├── post/
│   ├── view.phtml
│   ├── edit.phtml
│   └── list.phtml
```

### 5. Use Helper Methods

```php
// Create a view helper class
class TemplateHelper
{
    public function formatMoney(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
    
    public function activeClass(bool $isActive): string
    {
        return $isActive ? 'active' : '';
    }
}

// Pass to templates
$layout->context('h', new TemplateHelper());

// Use in templates
<span><?= $h->formatMoney(99.99) ?></span>
<li class="<?= $h->activeClass($isActive) ?>">Menu Item</li>
```

### 6. Cache Rendered Templates (Production)

```php
class CachedLayout extends Layout
{
    public function render(string $template): string
    {
        $cacheKey = 'template:' . $template;
        
        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }
        
        $rendered = parent::render($template);
        $this->cache->set($cacheKey, $rendered, 3600);
        
        return $rendered;
    }
}
```

## Common Patterns

### Flash Messages

```php
// In handler after action
$_SESSION['flash'] = [
    'type' => 'success',
    'message' => 'User created successfully'
];

// In layout
<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['type'] ?>">
        <?= $this->escape($_SESSION['flash']['message']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
```

### Form Handling

```php
// In handler
public function handle(RequestInterface $request): ResponseInterface
{
    $layout = $this->getLayout();
    
    if ($request->getMethod() === 'POST') {
        $data = $request->getParsedBody();
        // Process form...
    }
    
    $layout->context('formData', $data ?? []);
    $layout->context('errors', $errors ?? []);
    
    return $this->render('form/user.phtml');
}

// In template
<form method="POST">
    <div>
        <label>Name:</label>
        <input type="text" name="name" 
               value="<?= $this->escape($formData['name'] ?? '') ?>">
        <?php if (isset($errors['name'])): ?>
            <span class="error"><?= $this->escape($errors['name']) ?></span>
        <?php endif; ?>
    </div>
    
    <button type="submit">Submit</button>
</form>
```

### Pagination

```php
// In handler
$page = (int) $request->getQuery('page', 1);
$perPage = 20;
$total = $this->repository->count();
$pages = ceil($total / $perPage);

$layout->context('items', $this->repository->paginate($page, $perPage));
$layout->context('pagination', [
    'current' => $page,
    'total' => $pages,
]);

// In template
<div class="pagination">
    <?php for ($i = 1; $i <= $pagination['total']; $i++): ?>
        <a href="?page=<?= $i ?>" 
           class="<?= $i === $pagination['current'] ? 'active' : '' ?>">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>
```

## Related Documentation

- [Architecture Overview](./architecture.md) - Framework architecture
- [HTTP Handling](./http.md) - Using layouts in handlers
- [Configuration](./configuration.md) - Template path configuration

---

[← Back to Documentation Index](./README.md) | [Next: CLI Commands →](./cli.md)
