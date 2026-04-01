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

## Request

The `Request` is created automatically from PHP superglobals. In handlers, it arrives as the first argument:

```php
function (RequestInterface $request): ResponseInterface {
    $request->getUri();                        // "/users" (query string stripped)
    $request->getMethod();                     // "GET"
    $request->getAttribute('id');              // route param or middleware-set attribute
    $request->getPostData();                   // $_POST array
    $request->getPostDatum('email');           // single POST field
    $request->getQueryParams();                // $_GET array
    $request->getQueryParam('page', '1');      // single query param with default
    $request->getHeader('Content-Type');       // case-insensitive header lookup
    $request->getHeaders();                    // all headers
    $request->getRawBody();                    // raw request body string
    $request->getParsedBody();                 // JSON-decoded body (if Content-Type is application/json)
    $request->getServerData();                 // $_SERVER array
    $request->getServerDatum('REMOTE_ADDR');   // single server value
}
```

For testing, build requests from arrays:

```php
$request = Request::createFromArrays(
    query: ['page' => '2'],
    post: [],
    server: ['REQUEST_URI' => '/users', 'REQUEST_METHOD' => 'GET'],
    headers: ['Accept' => 'application/json'],
);
```

## Response

Every handler returns a `Response`:

```php
// Basic response
return Response::create()
    ->withStatus(200)
    ->withBody('Hello!')
    ->withHeader('X-Custom', 'value');

// Multi-value headers
return Response::create()
    ->withHeader('Set-Cookie', 'a=1')
    ->withAddedHeader('Set-Cookie', 'b=2');

// JSON response
return Response::json(['name' => 'Blackbeard'], 200);

// Redirect
return Response::redirect('/dashboard', 302);

// Callable body (for streaming large files)
return Response::create()
    ->withBody(function () {
        readfile('/path/to/large-file.zip');
    });
```

## Middleware

Middleware wraps the request/response cycle. Each middleware receives the request and a `$next` callable:

```php
$timing = function (RequestInterface $request, callable $next): ResponseInterface {
    $start = microtime(true);
    $response = $next($request);
    $ms = round((microtime(true) - $start) * 1000);
    return $response->withHeader('X-Response-Time', "{$ms}ms");
};
```

Middleware is applied outermost-first. The first middleware added runs first (wraps everything):

```php
$app = App::create()
    ->withRouter($router)
    ->withMiddleware($errorHandler, $logger, $session);
// Order: ErrorHandler -> Logger -> Session -> Router
```

Middleware can be applied at three levels:

```php
// App-level: applies to all requests
$app = $app->withMiddleware($globalMiddleware);

// Router-level: applies to all routes in this router
$router = $router->withMiddleware($routerMiddleware);

// Route-level: applies to a single route
$route = $route->withMiddleware($routeMiddleware);
```

## Built-in Middleware

### ErrorMiddleware

Catches all exceptions and returns a 500 response. Should be the outermost middleware.

```php
use thegroovetrain\PiratePHP\ErrorMiddleware;

// Default: returns "Internal Server Error"
$error = ErrorMiddleware::create();

// Custom error handler
$error = ErrorMiddleware::create()
    ->withErrorHandler(function (\Throwable $e, RequestInterface $request): ResponseInterface {
        return Response::create()
            ->withStatus(500)
            ->withBody("Error: {$e->getMessage()}");
    });
```

### LoggingMiddleware

Logs every request with method, URI, status code, and response time.

```php
use thegroovetrain\PiratePHP\LoggingMiddleware;
use thegroovetrain\PiratePHP\FileLogger;

$logger = FileLogger::create(__DIR__ . '/logs/app.log');
$logging = LoggingMiddleware::create($logger);

// Log output: [2026-03-31 12:00:00] GET /users 200 15ms
```

### SessionMiddleware

Starts a PHP session and attaches session/flash data to the request as attributes.

```php
use thegroovetrain\PiratePHP\SessionMiddleware;

$session = SessionMiddleware::create();

// With custom cookie params
$session = SessionMiddleware::create()
    ->withCookieParams(['lifetime' => 3600]);
```

See the [Sessions & Flash](#sessions--flash) section for reading/writing session data.

### StaticFileMiddleware

Serves static files from a directory. Validates paths with `realpath()` to prevent directory traversal.

```php
use thegroovetrain\PiratePHP\StaticFileMiddleware;

$static = StaticFileMiddleware::create(__DIR__ . '/public');

// With custom MIME types
$static = StaticFileMiddleware::create(__DIR__ . '/public', [
    'wasm' => 'application/wasm',
]);
```

### RateLimitMiddleware

File-based rate limiting per IP address. Uses `flock()` for write safety.

```php
use thegroovetrain\PiratePHP\RateLimitMiddleware;

// 100 requests per 60 seconds, storage in /tmp/rate-limit
$limiter = RateLimitMiddleware::create(100, 60, '/tmp/rate-limit');
```

If the limit is exceeded, returns a 429 response. If storage fails, the request is allowed through (graceful degradation).

### ContentNegotiationMiddleware

Automatically returns JSON or rendered HTML based on the `Accept` header. Checks for a `_data` attribute on the response.

```php
use thegroovetrain\PiratePHP\ContentNegotiationMiddleware;
use thegroovetrain\PiratePHP\PhpRenderer;

$renderer = PhpRenderer::create(__DIR__ . '/templates');
$negotiate = ContentNegotiationMiddleware::create($renderer, 'default.php');

// In your handler, set the _data attribute:
$route = Route::create()
    ->withPath('/users')
    ->withMethods('GET')
    ->withMiddleware($negotiate)
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $users = [['id' => 1, 'name' => 'Blackbeard']];
        return Response::create()->withAttribute('_data', $users);
    });
// Accept: application/json  => JSON response
// Accept: text/html          => rendered through default.php
```

## Templates

Use `PhpRenderer` to render PHP template files:

```php
use thegroovetrain\PiratePHP\PhpRenderer;

$renderer = PhpRenderer::create(__DIR__ . '/templates');

$route = Route::create()
    ->withPath('/')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request) use ($renderer): ResponseInterface {
        $html = $renderer->render('home.php', [
            'title' => 'Welcome',
            'name' => 'Pirate',
        ]);
        return Response::create()
            ->withBody($html)
            ->withHeader('Content-Type', 'text/html');
    });
```

Inside `templates/home.php`, variables are available directly:

```php
<h1><?= htmlspecialchars($title) ?></h1>
<p>Hello, <?= htmlspecialchars($name) ?>!</p>
```

Compose layouts by rendering a content template, then passing it into a layout:

```php
$content = $renderer->render('page.php', $data);
$html = $renderer->render('layout.php', ['content' => $content, 'title' => 'My Page']);
```

## Sessions & Flash

`SessionMiddleware` attaches a `PhpSession` object to the request for reading, and uses response attributes for writing.

### Reading session data

```php
use thegroovetrain\PiratePHP\SessionMiddleware;

$route = Route::create()
    ->withPath('/dashboard')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $session = $request->getAttribute(SessionMiddleware::ATTR_SESSION);
        $username = $session->get('username', 'Guest');

        return Response::create()->withBody("Hello, {$username}!");
    });
```

### Writing session data

Write to the session by setting an attribute on the response:

```php
$route = Route::create()
    ->withPath('/login')
    ->withMethods('POST')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $name = $request->getPostDatum('name');

        return Response::redirect('/dashboard')
            ->withAttribute(SessionMiddleware::ATTR_SESSION_WRITES, [
                'username' => $name,
            ]);
    });
```

### Flash messages

Flash messages are read from the previous request and cleared automatically. Write flash data to the response:

```php
// Writing a flash message (available on the NEXT request)
return Response::redirect('/form')
    ->withAttribute(SessionMiddleware::ATTR_FLASH_WRITES, [
        'message' => 'Form submitted successfully!',
    ]);

// Reading flash messages (from the PREVIOUS request)
$flash = $request->getAttribute(SessionMiddleware::ATTR_FLASH);
$message = $flash->get('message');  // "Form submitted successfully!" or null
```

## Cookies

Use `CookieJar` to read cookies from the request, and `withAddedHeader('Set-Cookie', ...)` to write them:

```php
use thegroovetrain\PiratePHP\CookieJar;

$route = Route::create()
    ->withPath('/preferences')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        // Read cookies
        $cookieHeader = $request->getHeader('cookie') ?? '';
        $cookies = CookieJar::fromHeaderString($cookieHeader);
        $theme = $cookies->get('theme', 'light');

        // Write a cookie
        return Response::create()
            ->withBody("Theme: {$theme}")
            ->withAddedHeader('Set-Cookie', 'theme=dark; HttpOnly; SameSite=Lax; Path=/');
    });
```

## Example App

A full working demo lives in `examples/`. To run it:

```bash
cd examples
php -S localhost:8000 -t public/
```

Then visit [http://localhost:8000](http://localhost:8000). The example demonstrates:

- Template rendering with layouts
- Form handling with POST, validation, and flash messages
- Dynamic route parameters
- Named routes with `urlFor()` link generation
- JSON API endpoints with route composition
- Static file serving (CSS)
- Request logging to file

## Testing

Build requests from arrays for unit testing without a running server:

```php
use thegroovetrain\PiratePHP\Request;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\Route;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\ResponseInterface;

// Create a test request
$request = Request::createFromArrays(
    query: ['page' => '1'],
    post: [],
    server: [
        'REQUEST_URI' => '/users',
        'REQUEST_METHOD' => 'GET',
    ],
    headers: ['Accept' => 'application/json'],
);

// Test a handler directly
$handler = function (RequestInterface $request): ResponseInterface {
    return Response::json(['users' => []]);
};

$response = $handler($request);
assert($response->getStatusCode() === 200);
assert($response->getHeader('Content-Type') === 'application/json');

// Test a full route through the middleware pipeline
$route = Route::create()
    ->withPath('/users')
    ->withMethods('GET')
    ->withMiddleware($someMiddleware)
    ->withHandler($handler);

$response = $route->handle($request);
```

## License

GPL-3.0
