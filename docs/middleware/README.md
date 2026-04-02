# Middleware Overview

PiratePHP uses the **onion model** for middleware. Each middleware wraps the next one in the stack. The request travels inward through each layer, hits the core handler, and then the response travels back outward through the same layers in reverse order.

## Table of Contents

- [The Onion Pipeline](#the-onion-pipeline)
- [Three Middleware Levels](#three-middleware-levels)
- [The $next Callback Pattern](#the-next-callback-pattern)
- [Writing Custom Middleware](#writing-custom-middleware)
- [Middleware Ordering](#middleware-ordering)
- [Middleware and Route Composition](#middleware-and-route-composition)
- [Built-in Middleware](#built-in-middleware)

---

## The Onion Pipeline

Every middleware receives the request and a `$next` callable. It can:

- Modify the request before passing it inward
- Short-circuit by returning a response without calling `$next`
- Modify the response on the way back out
- Wrap `$next` in a try/catch to handle errors

```
               Request
                 |
                 v
  +-----------------------------------+
  |        App Middleware              |
  |  +-----------------------------+  |
  |  |     Router Middleware       |  |
  |  |  +-----------------------+  |  |
  |  |  |   Route Middleware    |  |  |
  |  |  |  +-----------------+  |  |  |
  |  |  |  | Route Handler   |  |  |  |
  |  |  |  +-----------------+  |  |  |
  |  |  +-----------------------+  |  |
  |  +-----------------------------+  |
  +-----------------------------------+
                 |
                 v
              Response
```

---

## Three Middleware Levels

Middleware runs at three levels, from outermost to innermost:

1. **App** -- added via `$app->withMiddleware(...)`. Runs on every request regardless of which router or route matches.
2. **Router** -- added via `$router->withMiddleware(...)`. Runs only when the request URI matches the router's base path.
3. **Route** -- added via `$route->withMiddleware(...)`. Runs only when the specific route matches.

The pipeline is powered by the `HasMiddleware` trait. Here is the execution flow in detail:

```
Request arrives at App::handle()
  |
  +-- App middleware[0] receives $request and $next
  |     |
  |     +-- App middleware[1] receives $request and $next
  |           |
  |           +-- ... (all app middleware)
  |                 |
  |                 +-- App::handleRequest() selects a Router
  |                       |
  |                       +-- Router middleware[0] receives $request and $next
  |                             |
  |                             +-- ... (all router middleware)
  |                                   |
  |                                   +-- Router::handleRequest() matches a Route
  |                                         |
  |                                         +-- Route middleware[0]
  |                                               |
  |                                               +-- ... (all route middleware)
  |                                                     |
  |                                                     +-- Route handler ($request) => $response
  |                                                     |
  |                                               +-- Route middleware returns
  |                                         |
  |                                   +-- Router middleware returns
  |                             |
  |                       +-- App middleware returns
  |
  v
Response sent
```

The `HasMiddleware` trait implements this recursively. Each middleware at index `$index` receives a `$next` function that calls the middleware at `$index + 1`. When the last middleware calls `$next`, it invokes `handleRequest()` -- the core logic of the App, Router, or Route.

---

## The $next Callback Pattern

A middleware is any callable with this signature:

```php
function (RequestInterface $request, callable $next): ResponseInterface
```

The `$next` callable represents the rest of the middleware stack plus the final handler. Calling `$next($request)` passes control to the next layer. Not calling it short-circuits the pipeline and returns early.

---

## Writing Custom Middleware

A middleware can be a closure, an invokable class, or any callable. PiratePHP does not require middleware to implement an interface -- any callable with the right signature works.

### Example 1: Timing Middleware

This middleware measures how long the downstream pipeline takes and logs the elapsed time.

```php
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\ResponseInterface;

$timing = function (RequestInterface $request, callable $next): ResponseInterface {
    $start = microtime(true);

    $response = $next($request);

    $elapsed = round((microtime(true) - $start) * 1000);
    error_log("{$request->getMethod()} {$request->getUri()} - {$elapsed}ms");

    return $response;
};

$app = App::create()
    ->withMiddleware($timing)
    ->withRouter($router);
```

### Example 2: Auth Middleware That Short-Circuits

This middleware checks for an API key and returns a 401 response immediately if it is missing. It never calls `$next`, so the route handler never executes.

```php
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\ResponseInterface;
use thegroovetrain\PiratePHP\Response;

$auth = function (RequestInterface $request, callable $next): ResponseInterface {
    $apiKey = $request->getHeader('x-api-key');

    if ($apiKey !== 'my-secret-key') {
        return Response::create()
            ->withStatus(401)
            ->withBody('Unauthorized')
            ->withHeader('Content-Type', 'text/plain');
    }

    return $next($request);
};

$protectedRoute = Route::create()
    ->withPath('/api/secret')
    ->withMethods('GET')
    ->withMiddleware($auth)
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::json(['secret' => 'treasure map']);
    });
```

### Example 3: Middleware That Modifies the Response

This middleware adds security headers to every response on the way back out.

```php
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\ResponseInterface;

$securityHeaders = function (RequestInterface $request, callable $next): ResponseInterface {
    $response = $next($request);

    return $response
        ->withHeader('X-Content-Type-Options', 'nosniff')
        ->withHeader('X-Frame-Options', 'DENY')
        ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
};

$app = App::create()
    ->withMiddleware($securityHeaders)
    ->withRouter($router);
```

### Invokable Class Pattern

For middleware with configuration, use an invokable class with a private constructor and a static `create()` factory, following PiratePHP conventions:

```php
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\ResponseInterface;
use thegroovetrain\PiratePHP\Response;

class CorsMiddleware
{
    private string $allowedOrigin;

    private function __construct(string $allowedOrigin)
    {
        $this->allowedOrigin = $allowedOrigin;
    }

    public static function create(string $allowedOrigin): static
    {
        return new static($allowedOrigin);
    }

    public function withAllowedOrigin(string $origin): static
    {
        $new = clone $this;
        $new->allowedOrigin = $origin;
        return $new;
    }

    public function __invoke(RequestInterface $request, callable $next): ResponseInterface
    {
        $response = $next($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->allowedOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
```

---

## Middleware Ordering

Middleware executes in the order it is added. The first middleware added is the outermost layer of the onion. This order matters because each layer wraps everything inside it.

### Recommended Order

```php
$app = App::create()
    ->withMiddleware(
        ErrorMiddleware::create(),              // 1st: outermost
        LoggingMiddleware::create($logger),     // 2nd
        SessionMiddleware::create(),            // 3rd
        RateLimitMiddleware::create(100, 60, '/tmp/ratelimit'),  // 4th
        StaticFileMiddleware::create(__DIR__ . '/public'),       // 5th
    )
    ->withRouter($router);
```

### Why This Order

**ErrorMiddleware first.** It wraps everything in a try/catch. If any middleware or handler throws an exception, ErrorMiddleware catches it and returns a clean 500 response. If it were not first, an exception in an outer middleware would bypass it entirely and produce an ugly PHP error.

**LoggingMiddleware second.** It needs to be inside the error handler (so errors are caught) but outside everything else (so it can time the entire request lifecycle). Being second means it logs every request including those that hit rate limits or serve static files.

**SessionMiddleware third.** Sessions need to be started before any handler that reads session data. It must be outside rate limiting so that rate-limited requests do not needlessly start sessions -- but in practice, having sessions available for rate-limited requests is harmless, and session data may be needed by custom auth middleware that runs before rate limiting. Place it here for broad availability.

**RateLimitMiddleware fourth.** It should run after the session is available (in case you want to rate-limit by user ID in a custom variant) but before static file serving and route handlers. Rate-limited requests are rejected early, saving the cost of file I/O or database queries.

**StaticFileMiddleware fifth.** If the request matches a static file, it returns immediately without hitting the router. It should be inside rate limiting so that static file requests count toward the limit. Place it last among the app-level middleware so it acts as a fast exit before routing.

**ContentNegotiationMiddleware** is typically added at the router or route level, not app-level, because it is specific to API routes that return structured data.

---

## Middleware and Route Composition

Because PiratePHP objects are immutable, `withPath()` concatenates onto the existing path and `withMiddleware()` accumulates onto the existing middleware stack. This means you can build a "base" route with shared configuration and derive specific routes from it. This is PiratePHP's route group pattern -- without a `RouteGroup` class.

### The Pattern

```php
use thegroovetrain\PiratePHP\Route;

// Define a base route with shared path prefix and middleware
$apiBase = Route::create()
    ->withPath('/api/v1')
    ->withMiddleware($authMiddleware, $corsMiddleware);

// Derive specific routes -- each inherits /api/v1 prefix and both middleware
$getUsers = $apiBase
    ->withPath('/users')           // path is now /api/v1/users
    ->withMethods('GET')
    ->withHandler($listUsersHandler);

$getUser = $apiBase
    ->withPath('/users/:id')       // path is now /api/v1/users/:id
    ->withMethods('GET')
    ->withHandler($getUserHandler);

$createUser = $apiBase
    ->withPath('/users')           // path is now /api/v1/users
    ->withMethods('POST')
    ->withMiddleware($validateBody) // middleware: $authMiddleware, $corsMiddleware, $validateBody
    ->withHandler($createUserHandler);
```

Because `$apiBase` is immutable, deriving `$getUsers` from it does not change `$apiBase`. Each derived route gets its own copy of the path and middleware stack.

### Nested Composition

You can compose multiple levels deep:

```php
// Level 1: all API routes need auth
$api = Route::create()
    ->withPath('/api')
    ->withMiddleware($authMiddleware);

// Level 2: admin routes need an additional admin check
$admin = $api
    ->withPath('/admin')           // path is now /api/admin
    ->withMiddleware($adminOnly);  // middleware: $authMiddleware, $adminOnly

// Level 3: specific admin endpoints
$adminUsers = $admin
    ->withPath('/users')           // path is now /api/admin/users
    ->withMethods('GET')
    ->withHandler($adminListUsers);

$adminDelete = $admin
    ->withPath('/users/:id')       // path is now /api/admin/users/:id
    ->withMethods('DELETE')
    ->withMiddleware($auditLog)    // middleware: $authMiddleware, $adminOnly, $auditLog
    ->withHandler($adminDeleteUser);
```

The middleware stack for `$adminDelete` is `[$authMiddleware, $adminOnly, $auditLog]`, built up incrementally through composition.

### Combining with Router Middleware

Remember that middleware runs at three levels. You can use router-level middleware for shared concerns and route-level composition for finer control:

```php
$contentNeg = ContentNegotiationMiddleware::create($renderer, 'api.php');

$apiRouter = Router::create()
    ->withBasePath('/api')
    ->withMiddleware($contentNeg)  // all routes in this router get content negotiation
    ->withRoute($getUsers, $getUser, $createUser);

$webRouter = Router::create()
    ->withBasePath('/')
    ->withRoute($homePage, $aboutPage);  // no content negotiation here

$app = App::create()
    ->withMiddleware(
        ErrorMiddleware::create(),
        LoggingMiddleware::create($logger),
        SessionMiddleware::create(),
    )
    ->withRouter($apiRouter, $webRouter);
```

In this setup:

- **Every request** passes through ErrorMiddleware, LoggingMiddleware, and SessionMiddleware (app level).
- **Requests to `/api/*`** additionally pass through ContentNegotiationMiddleware (router level).
- **Individual routes** can add their own middleware (route level) via the composition pattern shown above.

This three-level system, combined with immutable route composition, gives you the flexibility of route groups without any special grouping API.

---

## Built-in Middleware

PiratePHP ships with six middleware classes. Each has its own documentation page:

| Middleware | Purpose |
|---|---|
| [ErrorMiddleware](error.md) | Catches exceptions and returns clean error responses |
| [LoggingMiddleware](logging.md) | Logs request method, URI, status, and timing |
| [SessionMiddleware](session.md) | Manages PHP sessions with immutable read/write-back |
| [StaticFileMiddleware](static-files.md) | Serves static files from a directory |
| [RateLimitMiddleware](rate-limiting.md) | Per-IP rate limiting with file-based storage |
| [ContentNegotiationMiddleware](content-negotiation.md) | Transforms data to JSON or HTML based on Accept header |
