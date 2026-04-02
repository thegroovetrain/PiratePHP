# Getting Started with PiratePHP

PiratePHP is an immutable PHP micro-framework with zero production dependencies, designed for building web applications through composition.

---

## Prerequisites

- **PHP 8.0** or later
- **Composer** ([https://getcomposer.org](https://getcomposer.org))

That is it. PiratePHP has zero production dependencies.

---

## Installation

Install via Composer:

```bash
composer require thegroovetrain/piratephp
```

Create a `public/` directory for your entry point:

```
project/
  public/
    index.php
  vendor/
  composer.json
```

The `public/` directory is the web root -- only `index.php` and static assets live here. Application code, templates, and vendor files stay outside the web root for security.

---

## Hello World

Create `public/index.php`:

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

---

## Understanding the Building Blocks

PiratePHP has four core objects that compose together: **Route**, **Router**, **App**, and **Response**. Everything is immutable -- every `with*()` method returns a new instance and the original is never modified.

### Route

A `Route` represents a single endpoint. You build one by chaining configuration methods:

```php
$route = Route::create()       // Start with an empty route
    ->withPath('/hello')       // Set the URL path to match
    ->withMethods('GET')       // Accept GET requests
    ->withHandler($handler);   // Attach a handler function
```

- `withPath(string $path)` -- Sets the URL path. Calling it multiple times concatenates paths (see [Routing](routing.md) for the composition pattern).
- `withMethods(string ...$methods)` -- Specifies which HTTP methods this route responds to. Pass one or more: `'GET'`, `'POST'`, `'PUT'`, `'DELETE'`, etc.
- `withHandler(callable $handler)` -- Attaches the function that handles matching requests.

### Router

A `Router` holds a collection of routes and matches incoming requests to them:

```php
$router = Router::create()
    ->withRoute($routeA, $routeB);
```

- `withRoute(RouteInterface ...$route)` -- Adds one or more routes. You can call it multiple times or pass them all at once.
- `withBasePath(string $basepath)` -- Sets a URL prefix for all routes in this router (see [Routing](routing.md)).

When a request arrives, the router tries each route in order. If the path matches but the method does not, it returns `405 Method Not Allowed`. If no path matches at all, it returns `404 Not Found`.

### App

`App` is the top-level container. It holds routers and middleware, and `run()` reads the HTTP request from superglobals, processes it through the pipeline, and sends the response:

```php
$app = App::create()
    ->withRouter($router)
    ->withMiddleware($someMiddleware);

$app->run();
```

- `withRouter(RouterInterface ...$router)` -- Adds one or more routers. When multiple routers exist, they are sorted by base path length (longest first) so more specific paths match before general ones.
- `withMiddleware(callable ...$middleware)` -- Adds app-level middleware that runs for every request.

### Response

`Response` represents the HTTP response your handler returns:

```php
$response = Response::create()
    ->withStatus(200)
    ->withBody('Hello!')
    ->withHeader('Content-Type', 'text/plain');
```

The default status is `200`, so you can skip `withStatus()` for success responses. For details on all response methods, see [Response](response.md).

---

## The Handler Contract

Every handler in PiratePHP follows the same signature:

```php
function (RequestInterface $request): ResponseInterface
```

The handler receives a `RequestInterface` containing all the HTTP request data (URI, method, headers, query parameters, body, route parameters) and must return a `ResponseInterface`.

This is the only contract you need to know. Whether the handler is an anonymous function, an invokable class, or a static method -- it receives a request and returns a response.

```php
// Anonymous function
$handler = function (RequestInterface $request): ResponseInterface {
    return Response::create()->withBody('OK');
};

// Invokable class
class HomeHandler {
    public function __invoke(RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('OK');
    }
}
$handler = new HomeHandler();
```

For the full request API, see [Request](request.md). For the full response API, see [Response](response.md).

---

## Running the Application

Use PHP's built-in development server:

```bash
php -S localhost:8080 -t public
```

This starts a server at `http://localhost:8080` with `public/` as the document root. The `-t` flag points to the directory containing `index.php`.

For production, configure your web server (Apache, Nginx) to route all requests to `public/index.php`. A typical Nginx configuration:

```nginx
location / {
    try_files $uri /index.php$is_args$args;
}
```

---

## Next Steps

- [Routing](routing.md) -- Dynamic parameters, route composition, named routes, and subrouters
- [Request](request.md) -- Reading query params, POST data, JSON bodies, and headers
- [Response](response.md) -- Status codes, JSON responses, redirects, and streaming
- [Templates](templates.md) -- Rendering PHP templates with PhpRenderer
- [Testing](testing.md) -- Unit testing your handlers and routes
