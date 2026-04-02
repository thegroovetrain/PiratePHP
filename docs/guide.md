# PiratePHP Tutorial Guide

A step-by-step guide to building web applications with PiratePHP, an immutable PHP micro-framework.

Everything in PiratePHP is immutable. Every `with*()` method returns a new instance -- the original is never modified. This makes your application easy to reason about and compose.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Installation](#2-installation)
3. [Hello World](#3-hello-world)
4. [Adding Routes](#4-adding-routes)
5. [Route Composition](#5-route-composition)
6. [Named Routes and URL Generation](#6-named-routes-and-url-generation)
7. [Request Handling](#7-request-handling)
8. [Response Helpers](#8-response-helpers)
9. [Middleware](#9-middleware)
10. [Error Handling](#10-error-handling)
11. [Sessions and Flash Messages](#11-sessions-and-flash-messages)
12. [Cookies](#12-cookies)
13. [Templates](#13-templates)
14. [Static Files](#14-static-files)
15. [Logging](#15-logging)
16. [Rate Limiting](#16-rate-limiting)
17. [Content Negotiation](#17-content-negotiation)
18. [Testing](#18-testing)
19. [Putting It All Together](#19-putting-it-all-together)

---

## 1. Prerequisites

- **PHP 8.0** or later
- **Composer** (https://getcomposer.org)

That's it. PiratePHP has zero production dependencies.

---

## 2. Installation

```bash
composer require thegroovetrain/piratephp
```

Set up a `public/index.php` as your entry point and point your web server at the `public/` directory. If you're developing locally, PHP's built-in server works fine:

```bash
php -S localhost:8080 -t public
```

---

## 3. Hello World

Here's the smallest PiratePHP application:

```php
<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use thegroovetrain\PiratePHP\App;
use thegroovetrain\PiratePHP\Router;
use thegroovetrain\PiratePHP\Route;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\ResponseInterface;

$route = Route::create()
    ->withPath('/')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()
            ->withBody('<h1>Hello from PiratePHP!</h1>')
            ->withHeader('Content-Type', 'text/html');
    });

$router = Router::create()
    ->withRoute($route);

$app = App::create()
    ->withRouter($router);

$app->run();
```

Here's what's happening:

- **`Route::create()`** creates an empty route. You chain `withPath()`, `withMethods()`, and `withHandler()` to configure it.
- **`Router::create()`** creates a router that holds routes. It matches incoming requests to routes by path and method.
- **`App::create()`** creates the application. It holds routers and middleware, and `run()` reads the incoming HTTP request, processes it through the middleware/router pipeline, and sends the response.

Every handler receives a `RequestInterface` and must return a `ResponseInterface`.

---

## 4. Adding Routes

Add multiple routes by passing them to `withRoute()`:

```php
$home = Route::create()
    ->withPath('/')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('Home page');
    });

$about = Route::create()
    ->withPath('/about')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('About page');
    });

$router = Router::create()
    ->withRoute($home, $about);
```

You can pass multiple routes at once or chain `withRoute()` calls -- both work.

### Dynamic Parameters

Use `:paramName` segments to capture dynamic values from the URL:

```php
$userRoute = Route::create()
    ->withPath('/user/:id')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::create()->withBody("User ID: {$id}");
    });
```

When a request hits `/user/42`, PiratePHP extracts `42` and attaches it as a request attribute. You read it with `$request->getAttribute('id')`.

You can use multiple parameters:

```php
$postRoute = Route::create()
    ->withPath('/user/:userId/post/:postId')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $userId = $request->getAttribute('userId');
        $postId = $request->getAttribute('postId');
        return Response::create()->withBody("User {$userId}, Post {$postId}");
    });
```

Parameter names must start with a letter or underscore and contain only word characters (letters, digits, underscores).

### Method Matching

If a path matches but the HTTP method doesn't, PiratePHP returns a `405 Method Not Allowed` response. If no path matches at all, it returns `404 Not Found`.

A route can accept multiple methods:

```php
$route = Route::create()
    ->withPath('/data')
    ->withMethods('GET', 'POST')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $method = $request->getMethod();
        return Response::create()->withBody("Method: {$method}");
    });
```

---

## 5. Route Composition

This is the PiratePHP way of organizing routes. The key insight: **`withPath()` concatenates**. If a route already has a path, calling `withPath()` appends to it.

Create a "template route" with a shared prefix, then derive child routes from it:

```php
// Template route: shared prefix, no handler yet
$apiBase = Route::create()->withPath('/api');

// Child routes: withPath() appends to "/api"
$listUsers = $apiBase
    ->withPath('/users')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::json([
            ['id' => 1, 'name' => 'Blackbeard'],
            ['id' => 2, 'name' => 'Anne Bonny'],
        ]);
    });

$getUser = $apiBase
    ->withPath('/users/:id')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::json(['id' => $id, 'name' => 'Pirate ' . $id]);
    });

$router = Router::create()
    ->withRoute($listUsers, $getUser);
```

`$listUsers` has the path `/api/users`. `$getUser` has the path `/api/users/:id`. Both were derived from `$apiBase` which provided the `/api` prefix.

Because everything is immutable, `$apiBase` is never modified. You can derive as many child routes from it as you like.

### Sharing Middleware via Template Routes

Template routes can also carry middleware that all child routes inherit:

```php
$authMiddleware = function (RequestInterface $request, callable $next): ResponseInterface {
    $token = $request->getHeader('authorization');
    if ($token !== 'Bearer secret-token') {
        return Response::json(['error' => 'Unauthorized'], 401);
    }
    return $next($request);
};

$adminBase = Route::create()
    ->withPath('/admin')
    ->withMiddleware($authMiddleware);

$dashboard = $adminBase
    ->withPath('/dashboard')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('Admin Dashboard');
    });

$settings = $adminBase
    ->withPath('/settings')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('Admin Settings');
    });
```

Both `/admin/dashboard` and `/admin/settings` run through `$authMiddleware` because they were derived from `$adminBase`.

---

## 6. Named Routes and URL Generation

Give a route a name with `withName()`, then generate its URL with `$router->urlFor()`:

```php
$userRoute = Route::create()
    ->withPath('/user/:id')
    ->withMethods('GET')
    ->withName('user.show')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::create()->withBody("User {$id}");
    });

$router = Router::create()
    ->withRoute($userRoute);

// Generate URLs
$url = $router->urlFor('user.show', ['id' => '42']);
// Result: "/user/42"
```

`urlFor()` replaces `:param` placeholders with the values you provide and URL-encodes them with `rawurlencode()`.

If the router has a base path, it's prepended automatically:

```php
$router = Router::create()
    ->withBasePath('/v1')
    ->withRoute($userRoute);

$url = $router->urlFor('user.show', ['id' => '42']);
// Result: "/v1/user/42"
```

If you reference a route name that doesn't exist, `urlFor()` throws a `\RuntimeException`.

---

## 7. Request Handling

PiratePHP's `Request` wraps the incoming HTTP data. In your handlers, you receive a `RequestInterface`.

### URI and Method

```php
$uri = $request->getUri();       // "/user/42" (query string stripped)
$method = $request->getMethod(); // "GET", "POST", etc.
```

### Query Parameters

For a URL like `/search?q=pirate&page=2`:

```php
$q = $request->getQueryParam('q');              // "pirate"
$page = $request->getQueryParam('page', '1');   // "2" (or "1" as default)
$all = $request->getQueryParams();              // ['q' => 'pirate', 'page' => '2']
```

### POST Data

For form submissions (`application/x-www-form-urlencoded`):

```php
$name = $request->getPostDatum('name');
$email = $request->getPostDatum('email', 'none');
$all = $request->getPostData();
```

### JSON Bodies

For `application/json` request bodies, use `getParsedBody()`:

```php
$data = $request->getParsedBody();
// Returns the decoded array/object, or null if decoding failed
```

`getParsedBody()` only decodes when the `Content-Type` header contains `application/json`. The result is cached -- calling it twice doesn't re-decode.

### Raw Body

```php
$raw = $request->getRawBody(); // The raw string from php://input
```

### Headers

```php
$contentType = $request->getHeader('Content-Type');  // Case-insensitive
$all = $request->getHeaders();                       // All headers (keys lowercased)
```

### Server Data

```php
$ip = $request->getServerDatum('REMOTE_ADDR');
$all = $request->getServerData();
```

### Attributes

Attributes are a general-purpose bag for passing data through middleware and handlers:

```php
$request = $request->withAttribute('user', $currentUser);
$user = $request->getAttribute('user');
$request = $request->withoutAttribute('user');
```

Route parameters (`:id`, etc.) are attached as attributes automatically.

---

## 8. Response Helpers

### Basic Response

```php
$response = Response::create()
    ->withStatus(200)
    ->withBody('Hello!')
    ->withHeader('Content-Type', 'text/plain');
```

The default status code is `200`, so you can skip `withStatus()` for success responses.

### JSON Response

```php
$response = Response::json(['name' => 'Blackbeard', 'crew' => 42]);
// Sets Content-Type: application/json and encodes the data

$response = Response::json(['error' => 'Not Found'], 404);
// With a custom status code
```

### Redirect

```php
$response = Response::redirect('/login');
// 302 redirect by default

$response = Response::redirect('/new-location', 301);
// Permanent redirect
```

### Callable Bodies

`withBody()` accepts a callable. This is useful for streaming or deferred output:

```php
$response = Response::create()
    ->withBody(function () {
        readfile('/path/to/large-file.csv');
    })
    ->withHeader('Content-Type', 'text/csv');
```

The callable is invoked when `send()` is called, not when the response is built.

### Headers

```php
// Replace a header
$response = $response->withHeader('X-Custom', 'value');

// Append a value to a header (useful for Set-Cookie)
$response = $response->withAddedHeader('Set-Cookie', 'name=value; Path=/');

// Set multiple headers at once
$response = $response->withHeaders([
    'X-One' => 'first',
    'X-Two' => 'second',
]);

// Remove headers
$response = $response->withoutHeaders('X-Custom', 'X-One');
```

### Response Attributes

Responses also have attributes, just like requests. This is how you communicate back to middleware (for example, session writes):

```php
$response = $response->withAttribute('key', 'value');
$value = $response->getAttribute('key');
```

---

## 9. Middleware

Middleware wraps around request handling. PiratePHP supports middleware at three levels:

- **App level** -- runs for every request
- **Router level** -- runs for requests matching the router's base path
- **Route level** -- runs only for the matched route

### The Middleware Signature

Every middleware is a callable with this shape:

```php
function (RequestInterface $request, callable $next): ResponseInterface {
    // Before: modify the request, check auth, etc.

    $response = $next($request); // Call the next middleware (or the handler)

    // After: modify the response, add headers, log, etc.

    return $response;
}
```

Call `$next($request)` to pass the request down the pipeline. You can short-circuit by returning a response without calling `$next`.

### Adding Middleware

```php
$app = App::create()
    ->withRouter($router)
    ->withMiddleware($middlewareA, $middlewareB);

$router = Router::create()
    ->withMiddleware($middlewareC);

$route = Route::create()
    ->withPath('/')
    ->withMethods('GET')
    ->withMiddleware($middlewareD)
    ->withHandler($handler);
```

Middleware runs in the order you add it. For the route above, the execution order would be: `$middlewareA` -> `$middlewareB` -> `$middlewareC` -> `$middlewareD` -> `$handler`.

### Writing Custom Middleware

Here's an example that adds a response header with timing information:

```php
$timingMiddleware = function (RequestInterface $request, callable $next): ResponseInterface {
    $start = microtime(true);

    $response = $next($request);

    $elapsed = round((microtime(true) - $start) * 1000);
    return $response->withHeader('X-Response-Time', "{$elapsed}ms");
};

$app = App::create()
    ->withRouter($router)
    ->withMiddleware($timingMiddleware);
```

Here's an auth guard that short-circuits:

```php
$authGuard = function (RequestInterface $request, callable $next): ResponseInterface {
    $token = $request->getHeader('authorization');
    if ($token === null) {
        return Response::json(['error' => 'Missing auth token'], 401);
    }
    // Attach the authenticated user to the request
    $request = $request->withAttribute('user', ['id' => 1, 'name' => 'Pirate']);
    return $next($request);
};
```

### Using Class-Based Middleware

PiratePHP's built-in middleware uses invokable classes (classes with `__invoke`). You can do the same:

```php
class CorsMiddleware
{
    private string $origin;

    public function __construct(string $origin = '*')
    {
        $this->origin = $origin;
    }

    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $response = $next($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->origin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}

$app = App::create()
    ->withRouter($router)
    ->withMiddleware(new CorsMiddleware('https://example.com'));
```

---

## 10. Error Handling

`ErrorMiddleware` catches any `\Throwable` thrown during request handling and returns a `500 Internal Server Error` instead of crashing.

### Basic Usage

```php
use thegroovetrain\PiratePHP\ErrorMiddleware;

$app = App::create()
    ->withRouter($router)
    ->withMiddleware(
        ErrorMiddleware::create()
    );
```

Add it as the first middleware so it catches errors from everything downstream.

### Custom Error Handler

Provide your own error handler to control the error response:

```php
$errorMiddleware = ErrorMiddleware::create()
    ->withErrorHandler(function (\Throwable $e, RequestInterface $request): ResponseInterface {
        // Log the error, notify someone, etc.
        error_log($e->getMessage());

        return Response::create()
            ->withStatus(500)
            ->withBody('<h1>Something went wrong</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>')
            ->withHeader('Content-Type', 'text/html');
    });

$app = App::create()
    ->withRouter($router)
    ->withMiddleware($errorMiddleware);
```

The error handler receives the thrown `\Throwable` and the original `RequestInterface`. If your custom error handler itself throws, `ErrorMiddleware` falls back to a plain `500 Internal Server Error` response.

---

## 11. Sessions and Flash Messages

`SessionMiddleware` manages PHP sessions and provides a flash message system.

### Setup

```php
use thegroovetrain\PiratePHP\SessionMiddleware;

$app = App::create()
    ->withRouter($router)
    ->withMiddleware(
        SessionMiddleware::create()
    );
```

You can customize the session cookie parameters:

```php
$session = SessionMiddleware::create()
    ->withCookieParams([
        'httponly' => true,
        'samesite' => 'Strict',
        'secure' => true,
    ]);
```

### Reading Session Data

`SessionMiddleware` attaches a session object to the request. Read it in your handler:

```php
$handler = function (RequestInterface $request): ResponseInterface {
    $session = $request->getAttribute(SessionMiddleware::ATTR_SESSION);

    $username = $session->get('username', 'Guest');
    $isLoggedIn = $session->has('username');
    $allData = $session->all();

    return Response::create()->withBody("Hello, {$username}!");
};
```

Use the constants `SessionMiddleware::ATTR_SESSION` and `SessionMiddleware::ATTR_FLASH` to access the attribute keys.

### Writing Session Data

To write data to the session, set it as a response attribute using `SessionMiddleware::ATTR_SESSION_WRITES`. The value must be an associative array of keys and values to write:

```php
$handler = function (RequestInterface $request): ResponseInterface {
    return Response::create()
        ->withBody('Logged in!')
        ->withAttribute(SessionMiddleware::ATTR_SESSION_WRITES, [
            'username' => 'Blackbeard',
            'role' => 'captain',
        ]);
};
```

`SessionMiddleware` reads this attribute from the response after your handler runs and writes the data to `$_SESSION`.

### Flash Messages

Flash messages are session data that survives exactly one request. They're perfect for showing a success or error message after a redirect.

**Writing a flash message** (set on the response, read on the next request):

```php
$formPostHandler = function (RequestInterface $request): ResponseInterface {
    $name = $request->getPostDatum('name', '');

    if (trim($name) === '') {
        return Response::redirect('/form')
            ->withAttribute(SessionMiddleware::ATTR_FLASH_WRITES, [
                'error' => 'Name is required.',
            ]);
    }

    return Response::redirect('/form')
        ->withAttribute(SessionMiddleware::ATTR_FLASH_WRITES, [
            'message' => "Thanks, {$name}!",
        ]);
};
```

**Reading flash messages** (on the next request):

```php
$formGetHandler = function (RequestInterface $request): ResponseInterface {
    $flash = $request->getAttribute(SessionMiddleware::ATTR_FLASH);

    $message = $flash->get('message');
    $error = $flash->get('error');

    $html = '';
    if ($message !== null) {
        $html .= '<div class="flash">' . htmlspecialchars($message) . '</div>';
    }
    if ($error !== null) {
        $html .= '<div class="flash error">' . htmlspecialchars($error) . '</div>';
    }
    $html .= '<form method="POST" action="/form">...</form>';

    return Response::create()->withBody($html)->withHeader('Content-Type', 'text/html');
};
```

The flash data is automatically cleared after being read -- it won't appear again on subsequent requests.

---

## 12. Cookies

PiratePHP provides `CookieJar` for reading cookies from the request.

### Reading Cookies

Parse cookies from the request's `Cookie` header:

```php
use thegroovetrain\PiratePHP\CookieJar;

$handler = function (RequestInterface $request): ResponseInterface {
    $cookieHeader = $request->getHeader('cookie') ?? '';
    $cookies = CookieJar::fromHeaderString($cookieHeader);

    $theme = $cookies->get('theme', 'light');
    $hasToken = $cookies->has('auth_token');
    $all = $cookies->all();

    return Response::create()->withBody("Theme: {$theme}");
};
```

### Writing Cookies

Set cookies by adding `Set-Cookie` headers to the response. Use `withAddedHeader()` so multiple cookies don't overwrite each other:

```php
$handler = function (RequestInterface $request): ResponseInterface {
    return Response::create()
        ->withBody('Cookie set!')
        ->withAddedHeader('Set-Cookie', 'theme=dark; Path=/; HttpOnly; SameSite=Lax')
        ->withAddedHeader('Set-Cookie', 'lang=en; Path=/; HttpOnly; SameSite=Lax');
};
```

To delete a cookie, set it with an expired date:

```php
$response = $response->withAddedHeader(
    'Set-Cookie',
    'theme=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT'
);
```

---

## 13. Templates

`PhpRenderer` renders plain PHP template files with data. No special syntax -- just PHP.

### Setup

```php
use thegroovetrain\PiratePHP\PhpRenderer;

$renderer = PhpRenderer::create(__DIR__ . '/../templates');
```

The path you pass is the base directory for all templates.

### Rendering

```php
$html = $renderer->render('home.php', ['title' => 'Welcome', 'name' => 'Pirate']);
```

Inside `templates/home.php`, the data array keys become local variables:

```php
<!-- templates/home.php -->
<h1><?= htmlspecialchars($title) ?></h1>
<p>Hello, <?= htmlspecialchars($name) ?>!</p>
```

**Always use `htmlspecialchars()` when outputting user-supplied data.** PiratePHP does not auto-escape. This is PHP -- you're in control.

### Layouts

There's no built-in layout system, but composing templates is straightforward. Render the inner template first, then pass the result to a layout:

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

### Using in Handlers

A common pattern is to create a render helper and close over the renderer:

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

### Error Handling

If a template file doesn't exist or is outside the base directory, `PhpRenderer` throws a `TemplateNotFoundException`. This prevents path traversal attacks -- you can't `render('../../../etc/passwd')`.

---

## 14. Static Files

`StaticFileMiddleware` serves static assets (CSS, JS, images, fonts) directly from a directory.

### Setup

```php
use thegroovetrain\PiratePHP\StaticFileMiddleware;

$app = App::create()
    ->withRouter($router)
    ->withMiddleware(
        StaticFileMiddleware::create(__DIR__)
    );
```

Point it at your `public/` directory (or wherever `index.php` lives). If a file like `public/css/style.css` exists and a request comes in for `/css/style.css`, the middleware serves it directly with the correct `Content-Type` header.

If the file doesn't exist, the middleware passes the request through to the next middleware (and eventually to your routes).

### How It Works

- Automatically detects MIME types for common file extensions (`.css`, `.js`, `.png`, `.jpg`, `.svg`, `.woff2`, `.pdf`, and more).
- Blocks access to dotfiles (`.env`, `.htaccess`, etc.) for security.
- Validates that resolved file paths stay within the base directory (prevents path traversal).
- Uses callable bodies with `readfile()` for efficient streaming.

### Custom MIME Types

Add your own MIME type mappings:

```php
$staticFiles = StaticFileMiddleware::create(__DIR__, [
    'wasm' => 'application/wasm',
    'avif' => 'image/avif',
]);
```

Or add them later:

```php
$staticFiles = StaticFileMiddleware::create(__DIR__)
    ->withMimeTypes(['wasm' => 'application/wasm']);
```

### Typical Directory Structure

```
project/
  public/
    index.php
    css/
      style.css
    js/
      app.js
    images/
      logo.png
  templates/
  vendor/
```

---

## 15. Logging

`LoggingMiddleware` logs every request with its method, URI, status code, and response time.

### Setup

```php
use thegroovetrain\PiratePHP\LoggingMiddleware;
use thegroovetrain\PiratePHP\FileLogger;

$logger = FileLogger::create(__DIR__ . '/../logs/app.log');

$app = App::create()
    ->withRouter($router)
    ->withMiddleware(
        LoggingMiddleware::create($logger)
    );
```

Make sure the `logs/` directory exists and is writable.

### Log Output

Each request produces a line like:

```
[2026-03-31 14:23:05] GET /user/42 200 3ms
```

### Custom Logger

`LoggingMiddleware` accepts any object implementing `LoggerInterface`:

```php
use thegroovetrain\PiratePHP\LoggerInterface;

class DatabaseLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        // Write to a database table, send to an external service, etc.
    }
}

$app = App::create()
    ->withRouter($router)
    ->withMiddleware(
        LoggingMiddleware::create(new DatabaseLogger())
    );
```

`FileLogger` silently swallows write failures so a logging problem never crashes your app. If you write a custom logger, consider doing the same.

---

## 16. Rate Limiting

`RateLimitMiddleware` limits requests per IP address using a file-based storage backend.

### Setup

```php
use thegroovetrain\PiratePHP\RateLimitMiddleware;

$rateLimiter = RateLimitMiddleware::create(
    maxRequests: 100,       // Max requests per window
    windowSeconds: 60,      // Window duration in seconds
    storagePath: __DIR__ . '/../storage/ratelimit'
);

$app = App::create()
    ->withRouter($router)
    ->withMiddleware($rateLimiter);
```

This allows 100 requests per minute per IP. When the limit is exceeded, the middleware returns a `429 Too Many Requests` response.

### How It Works

- Identifies clients by `REMOTE_ADDR`.
- Stores request counts in JSON files under the storage path (one file per IP hash).
- Uses file locking (`flock`) for safe concurrent access.
- If storage fails (permissions, disk full, etc.), the middleware degrades gracefully and allows the request through.

### Adjusting Limits

```php
$rateLimiter = RateLimitMiddleware::create(60, 60, '/tmp/ratelimit')
    ->withMaxRequests(200)
    ->withWindowSeconds(120);
```

---

## 17. Content Negotiation

`ContentNegotiationMiddleware` automatically formats response data as JSON or HTML based on the request's `Accept` header. This lets a single handler serve both API clients and browsers.

### Setup

```php
use thegroovetrain\PiratePHP\ContentNegotiationMiddleware;
use thegroovetrain\PiratePHP\PhpRenderer;

$renderer = PhpRenderer::create(__DIR__ . '/../templates');

$contentNeg = ContentNegotiationMiddleware::create($renderer, 'data.php');
```

The second argument is the default template to use for HTML rendering.

### Using It

In your handler, attach data to the response using the `ContentNegotiationMiddleware::ATTR_DATA` attribute instead of setting the body directly:

```php
$route = Route::create()
    ->withPath('/users')
    ->withMethods('GET')
    ->withMiddleware($contentNeg)
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $users = [
            ['id' => 1, 'name' => 'Blackbeard'],
            ['id' => 2, 'name' => 'Anne Bonny'],
        ];
        return Response::create()
            ->withAttribute(ContentNegotiationMiddleware::ATTR_DATA, $users);
    });
```

When the client sends `Accept: application/json`, they get JSON. Otherwise, the data is passed to your template as local variables.

### Template for HTML Rendering

If the data is an array, its keys become template variables. If it's not an array, it's available as `$data`:

```php
<!-- templates/data.php -->
<h1>Users</h1>
<ul>
<?php foreach ($data ?? [] as $user): ?>
    <li><?= htmlspecialchars($user['name']) ?></li>
<?php endforeach; ?>
</ul>
```

Note: when the data is a non-associative array (like a list of users), the middleware wraps it as `['data' => $yourArray]`, so use `$data` in the template.

---

## 18. Testing

PiratePHP makes testing easy. Use `Request::createFromArrays()` to create requests without relying on superglobals, then run them through your app or individual routes.

### Testing a Single Route

```php
use thegroovetrain\PiratePHP\Request;
use thegroovetrain\PiratePHP\Route;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\ResponseInterface;

$route = Route::create()
    ->withPath('/hello')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $name = $request->getQueryParam('name', 'World');
        return Response::create()->withBody("Hello, {$name}!");
    });

// Create a test request
$request = Request::createFromArrays(
    query: ['name' => 'Pirate'],
    server: ['REQUEST_URI' => '/hello', 'REQUEST_METHOD' => 'GET']
);

$response = $route->handle($request);

assert($response->getStatusCode() === 200);
assert($response->getBody() === 'Hello, Pirate!');
```

### Testing with a Router

```php
use thegroovetrain\PiratePHP\Router;

$router = Router::create()
    ->withRoute($route);

$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/hello', 'REQUEST_METHOD' => 'GET']
);

$response = $router->handle($request);
assert($response->getStatusCode() === 200);
```

### Testing the Full App Pipeline

```php
use thegroovetrain\PiratePHP\App;
use thegroovetrain\PiratePHP\ErrorMiddleware;

$app = App::create()
    ->withRouter($router)
    ->withMiddleware(ErrorMiddleware::create());

$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/hello', 'REQUEST_METHOD' => 'GET']
);

// App has a handle() method just like Router and Route
$response = $app->handle($request);
assert($response->getStatusCode() === 200);
```

### Testing POST Requests and JSON

```php
// Form POST
$request = Request::createFromArrays(
    post: ['name' => 'Blackbeard', 'email' => 'bb@pirate.ship'],
    server: ['REQUEST_URI' => '/form', 'REQUEST_METHOD' => 'POST']
);

// JSON POST
$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/api/users', 'REQUEST_METHOD' => 'POST'],
    headers: ['Content-Type' => 'application/json'],
    body: json_encode(['name' => 'Blackbeard'])
);

$data = $request->getParsedBody();
assert($data['name'] === 'Blackbeard');
```

### Testing 404 and 405

```php
// No route matches -> 404
$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/nonexistent', 'REQUEST_METHOD' => 'GET']
);
$response = $router->handle($request);
assert($response->getStatusCode() === 404);

// Path matches but method doesn't -> 405
$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/hello', 'REQUEST_METHOD' => 'DELETE']
);
$response = $router->handle($request);
assert($response->getStatusCode() === 405);
```

### PHPUnit Example

```php
use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\Request;
use thegroovetrain\PiratePHP\Route;
use thegroovetrain\PiratePHP\Router;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\ResponseInterface;

class AppTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $route = Route::create()
            ->withPath('/greet/:name')
            ->withMethods('GET')
            ->withHandler(function (RequestInterface $request): ResponseInterface {
                $name = $request->getAttribute('name');
                return Response::json(['greeting' => "Ahoy, {$name}!"]);
            });

        $this->router = Router::create()->withRoute($route);
    }

    public function testGreetReturnsJson(): void
    {
        $request = Request::createFromArrays(
            server: ['REQUEST_URI' => '/greet/Blackbeard', 'REQUEST_METHOD' => 'GET']
        );

        $response = $this->router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Ahoy, Blackbeard!', $body['greeting']);
    }

    public function testWrongMethodReturns405(): void
    {
        $request = Request::createFromArrays(
            server: ['REQUEST_URI' => '/greet/Blackbeard', 'REQUEST_METHOD' => 'POST']
        );

        $response = $this->router->handle($request);
        $this->assertEquals(405, $response->getStatusCode());
    }
}
```

---

## 19. Putting It All Together

The `examples/` directory in the PiratePHP repository contains a complete working application that demonstrates all the features covered in this guide:

- **Routes**: home, about, form (GET + POST), user profile with dynamic `:id`, and a JSON API endpoint
- **Route composition**: API routes built from a template route with shared `/api` prefix
- **Named routes**: `urlFor()` link generation on the about page
- **Templates**: PHP templates with a layout wrapper and `htmlspecialchars()` escaping
- **Sessions and flash messages**: form submission with validation, redirect, and flash feedback
- **Static files**: CSS served via `StaticFileMiddleware`
- **Logging**: every request logged to `examples/logs/app.log`
- **Error handling**: `ErrorMiddleware` catches exceptions app-wide

To run the example:

```bash
cd examples
php -S localhost:8080 -t public
```

Then open `http://localhost:8080` in your browser.

The example's entry point is `examples/public/index.php`. Read through it alongside this guide -- every pattern described here is demonstrated there.
