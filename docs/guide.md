# PiratePHP Quick-Start Guide

PiratePHP is an immutable PHP micro-framework with zero dependencies. This guide walks you from install to a working app in five minutes. Each section links to a detailed page for when you want to go deeper.

---

## Install

Requires PHP 8.0+ and Composer.

```bash
composer require thegroovetrain/piratephp
```

Create `public/index.php` as your entry point.

---

## Hello World

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

$router = Router::create()->withRoute($route);
$app = App::create()->withRouter($router);
$app->run();
```

Every handler receives a `RequestInterface` and returns a `ResponseInterface`. Everything is immutable -- every `with*()` call returns a new instance.

For a full walkthrough of App, Router, Route, and Response, see [Getting Started](getting-started.md).

---

## Add a Route with Parameters

Use `:paramName` to capture dynamic URL segments:

```php
$userRoute = Route::create()
    ->withPath('/user/:id')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::json(['id' => $id, 'name' => 'Pirate ' . $id]);
    });

$router = Router::create()->withRoute($route, $userRoute);
```

`Response::json()` encodes data and sets `Content-Type: application/json` automatically.

The **template route pattern** lets you share path prefixes and middleware across routes:

```php
$apiBase = Route::create()->withPath('/api');

$listUsers = $apiBase->withPath('/users')->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::json([['id' => 1, 'name' => 'Blackbeard']]);
    });

$getUser = $apiBase->withPath('/users/:id')->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::json(['id' => $id]);
    });
```

Because everything is immutable, `$apiBase` is never modified.

For dynamic parameters, named routes, subrouters, and the full composition pattern, see [Routing](routing.md).

---

## Add Middleware

Middleware wraps request handling. It receives a request and a `$next` callable:

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

Middleware works at three levels: App (every request), Router (matching base path), and Route (matching route only). You can short-circuit by returning a response without calling `$next`:

```php
$authGuard = function (RequestInterface $request, callable $next): ResponseInterface {
    if ($request->getHeader('authorization') === null) {
        return Response::json(['error' => 'Unauthorized'], 401);
    }
    return $next($request);
};

// Apply to specific routes via the template route pattern
$protectedBase = Route::create()->withPath('/admin')->withMiddleware($authGuard);
$dashboard = $protectedBase->withPath('/dashboard')->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('Admin Dashboard');
    });
```

PiratePHP includes built-in middleware for error handling (`ErrorMiddleware`), logging (`LoggingMiddleware`), rate limiting (`RateLimitMiddleware`), static files (`StaticFileMiddleware`), sessions (`SessionMiddleware`), and content negotiation (`ContentNegotiationMiddleware`).

---

## Add a Template

`PhpRenderer` renders plain PHP files with data:

```php
use thegroovetrain\PiratePHP\PhpRenderer;

$renderer = PhpRenderer::create(__DIR__ . '/../templates');

$route = Route::create()
    ->withPath('/')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request) use ($renderer): ResponseInterface {
        $html = $renderer->render('home.php', ['name' => 'Pirate']);
        return Response::create()->withBody($html)->withHeader('Content-Type', 'text/html');
    });
```

In `templates/home.php`, data keys become local variables:

```php
<h1>Hello, <?= htmlspecialchars($name) ?>!</h1>
```

Always use `htmlspecialchars()` for user-supplied data. PiratePHP does not auto-escape.

For layouts, render the inner template first, then pass it to a layout template. See [Templates](templates.md) for the full pattern.

---

## Run It

```bash
php -S localhost:8080 -t public
```

Open `http://localhost:8080` in your browser.

The `examples/` directory in the PiratePHP repository contains a complete working application demonstrating routes, middleware, templates, sessions, and static files.

---

## Detailed Documentation

- [Getting Started](getting-started.md) -- Prerequisites, installation, hello world, and the handler contract
- [Routing](routing.md) -- Dynamic params, route composition, named routes, subrouters
- [Request](request.md) -- Query params, POST data, JSON bodies, headers, attributes
- [Response](response.md) -- Status codes, JSON, redirects, streaming, headers
- [Templates](templates.md) -- PhpRenderer, layouts, escaping, path traversal protection
- [Cookies](cookies.md) -- CookieJar for reading, Set-Cookie headers for writing
- [Testing](testing.md) -- Request::createFromArrays(), PHPUnit patterns
