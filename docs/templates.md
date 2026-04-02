# Templates

PiratePHP's `PhpRenderer` renders plain PHP files as templates with data injection. There is no special template syntax -- just PHP.

---

## Setup

Create a renderer by pointing it at your templates directory:

```php
use thegroovetrain\PiratePHP\PhpRenderer;

$renderer = PhpRenderer::create(__DIR__ . '/../templates');
```

- `PhpRenderer::create(string $basePath): static` -- Creates a renderer with the given base directory for template files.
- `withBasePath(string $basePath): static` -- Returns a new renderer with a different base directory.
- `getBasePath(): string` -- Returns the current base directory.

---

## Rendering Templates

```php
$html = $renderer->render('home.php', ['title' => 'Welcome', 'name' => 'Pirate']);
```

- `render(string $template, array $data = []): string` -- Renders the template file and returns the output as a string.

The `$data` array keys become local variables inside the template via PHP's `extract()` (using `EXTR_SKIP`, so existing variables are not overwritten). Inside `templates/home.php`:

```php
<!-- templates/home.php -->
<h1><?= htmlspecialchars($title) ?></h1>
<p>Hello, <?= htmlspecialchars($name) ?>!</p>
```

---

## Output Escaping

PiratePHP does **not** auto-escape output. Always use `htmlspecialchars()` when outputting user-supplied data to prevent XSS attacks:

```php
<!-- Safe -->
<p><?= htmlspecialchars($userInput) ?></p>

<!-- UNSAFE -- do not do this with user data -->
<p><?= $userInput ?></p>
```

This is PHP -- you are in control of what gets escaped and what does not.

---

## Layout Composition

There is no built-in layout system, but composing templates is straightforward. Render the inner template first, then pass the result to a layout:

```php
$content = $renderer->render('home.php', ['name' => 'Pirate']);
$html = $renderer->render('layout.php', ['content' => $content, 'title' => 'Home']);
```

A layout template:

```php
<!-- templates/layout.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'My App') ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/about">About</a>
    </nav>

    <?= $content ?? '' ?>

    <footer>Powered by PiratePHP</footer>
</body>
</html>
```

### Render Helper Pattern

A common pattern is to create a helper function that wraps rendering with a layout:

```php
$renderer = PhpRenderer::create(__DIR__ . '/../templates');

function renderPage(PhpRenderer $renderer, string $template, array $data = [], string $title = 'My App'): string
{
    $content = $renderer->render($template, $data);
    return $renderer->render('layout.php', ['content' => $content, 'title' => $title]);
}

$route = Route::create()
    ->withPath('/')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request) use ($renderer): ResponseInterface {
        $html = renderPage($renderer, 'home.php', ['name' => 'Pirate'], 'Home');
        return Response::create()->withBody($html)->withHeader('Content-Type', 'text/html');
    });
```

---

## Path Traversal Protection

`PhpRenderer` validates that the resolved template path stays within the base directory. If a template file does not exist or resolves to a path outside the base directory, it throws a `TemplateNotFoundException`:

```php
// This would throw TemplateNotFoundException
$renderer->render('../../../etc/passwd');
```

The check uses `realpath()` to resolve symlinks and relative segments, then verifies the resolved path starts with the base directory path. This prevents directory traversal attacks.

---

## Related Pages

- [Getting Started](getting-started.md) -- Installation and hello world
- [Response](response.md) -- Using rendered HTML as the response body
- [Routing](routing.md) -- Connecting templates to route handlers
