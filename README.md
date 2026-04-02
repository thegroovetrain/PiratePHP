# PiratePHP

The immutable PHP micro-framework. Functional composition all the way down.

## Install

```
composer require thegroovetrain/piratephp
```

## Quick Start

```php
<?php declare(strict_types=1);

require 'vendor/autoload.php';

use thegroovetrain\PiratePHP\App;
use thegroovetrain\PiratePHP\Router;
use thegroovetrain\PiratePHP\Route;
use thegroovetrain\PiratePHP\Request;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\ResponseInterface;

$app = App::create()
    ->withRouter(Router::create()
        ->withRoute(Route::create()
            ->withPath('/')
            ->withMethods('GET')
            ->withHandler(function (RequestInterface $request): ResponseInterface {
                return Response::create()
                    ->withBody('Hello, World!');
            })
        )
    );

$app->run();
```

Every handler receives a `RequestInterface` and returns a `ResponseInterface`. That's the entire contract.

## Routing

### Basic Routes

```php
$route = Route::create()
    ->withPath('/hello')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('Hello!');
    });
```

### Dynamic Parameters

Route parameters are defined with `:param` syntax and accessed via `$request->getAttribute()`:

```php
$route = Route::create()
    ->withPath('/user/:id')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::create()->withBody("User #{$id}");
    });
```

### Named Routes

Name routes for URL generation:

```php
$route = Route::create()
    ->withPath('/user/:id')
    ->withMethods('GET')
    ->withName('user.show')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::create()->withBody("User #{$id}");
    });

$router = Router::create()->withRoute($route);

// Generate URLs from names
$url = $router->urlFor('user.show', ['id' => '42']);
// => "/user/42"
```

### Route Composition

Since `withPath()` concatenates, you can create a base route with a shared prefix and middleware, then compose child routes from it:

```php
$apiBase = Route::create()
    ->withPath('/api')
    ->withMiddleware($authMiddleware);

$listUsers = $apiBase
    ->withPath('/users')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::json(['users' => []]);
    });

$getUser = $apiBase
    ->withPath('/users/:id')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::json(['id' => $id]);
    });

$router = Router::create()->withRoute($listUsers, $getUser);
// Routes are /api/users and /api/users/:id, both inherit $authMiddleware
```

This is the core of PiratePHP's design: immutable composition. Every `withPath()` call builds on the previous path. Every `withMiddleware()` call adds to the existing stack. You compose routes like building blocks.

### Subrouters

Use separate routers with base paths for modular apps:

```php
$adminRouter = Router::create()
    ->withBasePath('/admin')
    ->withMiddleware($authMiddleware)
    ->withRoute($dashboardRoute, $settingsRoute);

$app = App::create()
    ->withRouter($adminRouter)
    ->withRouter($publicRouter);
```

## Features

- **Immutable composition** ... every object uses `with*`/clone. No mutations, no surprises.
- **Route composition** ... `withPath()` concatenates. Create a base route, compose children with shared prefix + middleware.
- **Named routes** ... `withName()` + `urlFor()` for URL generation.
- **JSON support** ... `Response::json()`, `Request::getParsedBody()` for JSON request bodies.
- **4 built-in middleware** ... ErrorMiddleware, LoggingMiddleware, RateLimitMiddleware, ContentNegotiationMiddleware.
- **Template rendering** ... `PhpRenderer` with plain PHP templates. No new syntax to learn.
- **Sessions & flash messages** ... first-class session support in Request/Response. Immutable reads via `getSession()`, writes via `withSession()` and `withFlash()`.
- **Cookies** ... `CookieJar` for reading, `withAddedHeader('Set-Cookie', ...)` for writing.
- **Rate limiting** ... per-IP throttling with file-based storage and flock atomicity.
- **Zero dependencies** ... everything is built in-house.
- **Test-friendly** ... `Request::createFromArrays()` lets you test the full pipeline without superglobals.

## Documentation

| Document | What it covers |
|----------|---------------|
| [Quick Start Guide](docs/guide.md) | Install, build your first app in 5 minutes |
| [Full Documentation](docs/README.md) | Complete docs index with all topics |
| [API Reference](docs/api.md) | Every class, method, and parameter |
| [Architecture](docs/architecture.md) | Design philosophy, request lifecycle, security model |

## Example App

A working demo app lives in `examples/`. It demonstrates routing, form handling, flash messages, JSON APIs, sessions, named routes, and template rendering.

```bash
cd examples
php -S localhost:8000 -t public/
```

Then visit [http://localhost:8000](http://localhost:8000).

## License

GPL-3.0
