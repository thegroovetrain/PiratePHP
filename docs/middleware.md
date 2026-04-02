# Middleware Deep Dive

## Table of Contents

1. [How Middleware Works](#how-middleware-works)
2. [Writing Custom Middleware](#writing-custom-middleware)
3. [Middleware Ordering](#middleware-ordering)
4. [Built-in Middleware](#built-in-middleware)
   - [ErrorMiddleware](#errormiddleware)
   - [LoggingMiddleware](#loggingmiddleware)
   - [SessionMiddleware](#sessionmiddleware)
   - [StaticFileMiddleware](#staticfilemiddleware)
   - [RateLimitMiddleware](#ratelimitmiddleware)
   - [ContentNegotiationMiddleware](#contentnegotiationmiddleware)
5. [Middleware and Route Composition](#middleware-and-route-composition)

---

## How Middleware Works

PiratePHP uses the **onion model** for middleware. Each middleware wraps the next one in the stack. The request travels inward through each layer, hits the core handler, and then the response travels back outward through the same layers in reverse order.

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

## Writing Custom Middleware

A middleware is any callable with this signature:

```php
function (RequestInterface $request, callable $next): ResponseInterface
```

It can be a closure, an invokable class, or any callable. PiratePHP does not require middleware to implement an interface -- any callable with the right signature works.

### Example 1: Simple Logging Middleware

This middleware logs the request method and URI, then passes through without modification.

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

## Built-in Middleware

### ErrorMiddleware

#### What It Does

ErrorMiddleware wraps the entire downstream pipeline in a try/catch. If any middleware or route handler throws a `\Throwable`, it catches the exception and returns a clean error response instead of letting PHP crash. You can provide a custom error handler to control the response format, log the error, or render a custom error page. If no handler is set (or the handler itself throws), it returns a plain-text `500 Internal Server Error`.

#### Configuration

**Constructor:**

```php
ErrorMiddleware::create(): static
```

The constructor takes no arguments. Private constructor, static factory.

**Configuration methods:**

```php
public function withErrorHandler(callable $handler): static
```

`$handler` receives two arguments: `(\Throwable $e, RequestInterface $request)` and must return a `ResponseInterface`. It is stored internally as a `\Closure`.

#### How to Use It

Basic usage with default 500 response:

```php
use thegroovetrain\PiratePHP\ErrorMiddleware;

$app = App::create()
    ->withMiddleware(ErrorMiddleware::create())
    ->withRouter($router);
```

With a custom error handler:

```php
use thegroovetrain\PiratePHP\ErrorMiddleware;
use thegroovetrain\PiratePHP\Response;

$errorMiddleware = ErrorMiddleware::create()
    ->withErrorHandler(function (\Throwable $e, RequestInterface $request): ResponseInterface {
        error_log($e->getMessage());

        return Response::create()
            ->withStatus(500)
            ->withBody(json_encode(['error' => $e->getMessage()]))
            ->withHeader('Content-Type', 'application/json');
    });

$app = App::create()
    ->withMiddleware($errorMiddleware)
    ->withRouter($router);
```

#### What It Reads from the Request

The request is passed through to `$next` unchanged. If a custom error handler is configured, the original request is forwarded to the handler as its second argument.

#### What It Writes to the Response

On success: nothing. It passes the downstream response through untouched.

On error without a custom handler: returns `Response::create()->withStatus(500)->withBody('Internal Server Error')` with `Content-Type: text/plain`.

On error when the custom handler also throws: returns the same default 500 response.

#### Edge Cases and Gotchas

- If your custom error handler itself throws an exception, ErrorMiddleware catches that too and falls back to the default plain-text 500 response. You will not see the handler's exception -- it is silently swallowed.
- ErrorMiddleware catches `\Throwable`, not just `\Exception`. This includes `TypeError`, `Error`, `ParseError`, and any other throwable.
- Place ErrorMiddleware as the **outermost** middleware. If another middleware outside it throws, the error will not be caught.

---

### LoggingMiddleware

#### What It Does

LoggingMiddleware records one log line per request containing the timestamp, HTTP method, URI, response status code, and elapsed time in milliseconds. It uses PiratePHP's `LoggerInterface`, so you can plug in any logger implementation. The built-in `FileLogger` writes to a file on disk.

#### Configuration

**Constructor:**

```php
LoggingMiddleware::create(LoggerInterface $logger): static
```

Takes a single required argument: a `LoggerInterface` implementation.

There are no `with*()` configuration methods on LoggingMiddleware itself.

**LoggerInterface:**

```php
interface LoggerInterface
{
    public function log(string $message): void;
}
```

**Built-in FileLogger:**

```php
FileLogger::create(string $filePath): static
```

```php
public function withFilePath(string $filePath): static
```

#### How to Use It

```php
use thegroovetrain\PiratePHP\LoggingMiddleware;
use thegroovetrain\PiratePHP\FileLogger;

$logger = FileLogger::create(__DIR__ . '/logs/app.log');

$app = App::create()
    ->withMiddleware(
        ErrorMiddleware::create(),
        LoggingMiddleware::create($logger),
    )
    ->withRouter($router);
```

Log output format:

```
[2026-03-31 14:22:05] GET /api/users 200 12ms
```

#### What It Reads from the Request

- `$request->getMethod()` -- the HTTP method (e.g., `GET`, `POST`)
- `$request->getUri()` -- the request URI path

#### What It Writes to the Response

Nothing. The response passes through unchanged.

#### Edge Cases and Gotchas

- Logger failures are silently swallowed. If `$this->logger->log()` throws, the exception is caught and ignored. Your application will not crash because of a logging failure.
- Timing starts before `$next` is called and ends after the response is returned. The elapsed time includes all downstream middleware and the route handler.
- The `FileLogger` strips newlines from log messages to prevent log injection attacks.
- The `FileLogger` uses `LOCK_EX` for file writes, making it safe for concurrent requests.
- If `getMethod()` returns `null`, the log line shows `UNKNOWN`. If `getUri()` returns `null`, it shows `/`.

---

### SessionMiddleware

#### What It Does

SessionMiddleware manages PHP sessions within PiratePHP's immutable architecture. It starts the session, reads existing session data and flash data into read-only `PhpSession` objects, attaches them to the request as attributes, calls the downstream handler, reads write-back instructions from the response attributes, persists changes to `$_SESSION`, and closes the session. This is the bridge between PHP's mutable session mechanism and PiratePHP's immutable request/response objects.

#### Configuration

**Constructor:**

```php
SessionMiddleware::create(): static
```

No arguments. Default cookie parameters: `httponly = true`, `samesite = 'Lax'`, `secure = false`.

**Configuration methods:**

```php
public function withCookieParams(array $params): static
```

Merges the provided array into the existing cookie parameters. Any keys in `$params` override the defaults.

**Constants:**

| Constant | Value | Used On |
|---|---|---|
| `SessionMiddleware::ATTR_SESSION` | `'_pirate_session'` | Request attribute |
| `SessionMiddleware::ATTR_FLASH` | `'_pirate_flash'` | Request attribute |
| `SessionMiddleware::ATTR_SESSION_WRITES` | `'_pirate_session_writes'` | Response attribute |
| `SessionMiddleware::ATTR_FLASH_WRITES` | `'_pirate_flash_writes'` | Response attribute |

#### How to Use It

**Setup:**

```php
use thegroovetrain\PiratePHP\SessionMiddleware;

$session = SessionMiddleware::create()
    ->withCookieParams([
        'secure' => true,
        'samesite' => 'Strict',
        'lifetime' => 3600,
    ]);

$app = App::create()
    ->withMiddleware($session)
    ->withRouter($router);
```

**Reading session data in a handler:**

```php
use thegroovetrain\PiratePHP\SessionMiddleware;

$handler = function (RequestInterface $request): ResponseInterface {
    $session = $request->getAttribute(SessionMiddleware::ATTR_SESSION);
    $flash = $request->getAttribute(SessionMiddleware::ATTR_FLASH);

    $username = $session->get('username', 'Guest');
    $message = $flash->get('success_message');

    return Response::create()
        ->withBody("Hello, {$username}! {$message}");
};
```

**Writing session data from a handler:**

```php
use thegroovetrain\PiratePHP\SessionMiddleware;

$loginHandler = function (RequestInterface $request): ResponseInterface {
    // ... validate credentials ...

    return Response::redirect('/dashboard')
        ->withAttribute(SessionMiddleware::ATTR_SESSION_WRITES, [
            'username' => 'blackbeard',
            'user_id' => 42,
        ])
        ->withAttribute(SessionMiddleware::ATTR_FLASH_WRITES, [
            'success_message' => 'Welcome aboard, Captain!',
        ]);
};
```

#### The Immutable Write-Back Pattern (Detailed Explanation)

This is the most important concept to understand in PiratePHP's session handling.

**The problem:** PHP sessions are inherently mutable -- you write to `$_SESSION` directly. But PiratePHP's requests and responses are immutable. A handler cannot mutate the request it received, and it cannot reach out and modify `$_SESSION` directly (well, it *could*, but that would break the immutable contract).

**The solution:** SessionMiddleware uses a two-phase approach. Session *reads* flow in through request attributes. Session *writes* flow out through response attributes. The middleware itself is the only code that touches `$_SESSION`.

Here is the full lifecycle:

```
 1. SessionMiddleware::__invoke() is called
        |
 2. session_start()
        |
 3. Read $_SESSION into PhpSession object (read-only)
        |
 4. Read $_SESSION['_flash'] into PhpSession object (read-only)
        |
 5. Delete $_SESSION['_flash'] (flash data is one-time-use)
        |
 6. Attach both PhpSession objects to $request as attributes:
    - SessionMiddleware::ATTR_SESSION  => session PhpSession
    - SessionMiddleware::ATTR_FLASH    => flash PhpSession
        |
 7. Call $next($request) -- request flows downstream
        |
        |   ... handler reads from $request->getAttribute(SessionMiddleware::ATTR_SESSION) ...
        |   ... handler attaches writes to $response->withAttribute(SessionMiddleware::ATTR_SESSION_WRITES, [...]) ...
        |
 8. Response comes back from $next()
        |
 9. Read SessionMiddleware::ATTR_SESSION_WRITES from $response
        |
10. Read SessionMiddleware::ATTR_FLASH_WRITES from $response
        |
11. Write each key/value from ATTR_SESSION_WRITES into $_SESSION
        |
12. Write ATTR_FLASH_WRITES array into $_SESSION['_flash']
        |
13. session_write_close()
        |
14. Return $response
```

**Reading session data:** The `PhpSession` object attached to the request is read-only. It has three methods:

```php
$session->get(string $key, mixed $default = null): mixed
$session->has(string $key): bool
$session->all(): array
```

There is no `set()` method. You cannot modify it. This is by design.

**Writing session data:** To write to the session, attach an associative array to the *response* using `SessionMiddleware::ATTR_SESSION_WRITES`. Each key/value pair in the array will be written to `$_SESSION` after the handler returns.

```php
// This tells SessionMiddleware to write these values
$response = $response->withAttribute(SessionMiddleware::ATTR_SESSION_WRITES, [
    'cart_count' => 3,
    'last_page' => '/products',
]);
```

**Flash data:** Flash data survives for exactly one request. When you write flash data, it is stored in `$_SESSION['_flash']`. On the *next* request, SessionMiddleware reads it out, attaches it to the request as `SessionMiddleware::ATTR_FLASH`, and then immediately deletes it from `$_SESSION`. Flash data is perfect for success/error messages after a redirect.

```php
// In the POST handler (request 1): write flash
$response = Response::redirect('/dashboard')
    ->withAttribute(SessionMiddleware::ATTR_FLASH_WRITES, [
        'notice' => 'Profile updated successfully.',
    ]);

// In the GET handler (request 2): read flash
$flash = $request->getAttribute(SessionMiddleware::ATTR_FLASH);
$notice = $flash->get('notice'); // "Profile updated successfully."

// In the GET handler (request 3): flash is gone
$flash = $request->getAttribute(SessionMiddleware::ATTR_FLASH);
$notice = $flash->get('notice'); // null
```

#### What It Reads from the Request

- `$request->getServerData()` -- checks for `HTTPS` key to auto-set the `secure` cookie flag

#### What It Writes to the Response

Nothing is written to the response. SessionMiddleware reads `ATTR_SESSION_WRITES` and `ATTR_FLASH_WRITES` from the response, but it does not add or modify headers or body. The session cookie is set by PHP's native `session_start()`.

#### Edge Cases and Gotchas

- **Session start failure:** If `session_start()` throws (e.g., headers already sent), SessionMiddleware catches the exception and calls `$next($request)` without attaching any session attributes. Your handler should check that `$request->getAttribute(SessionMiddleware::ATTR_SESSION)` is not `null` before calling methods on it.
- **HTTPS auto-detection:** The `secure` cookie parameter is automatically set to `true` if the `HTTPS` key exists in the server data, overriding whatever you configured via `withCookieParams()`.
- **Write-back is not a merge with existing session.** `ATTR_SESSION_WRITES` writes individual keys into `$_SESSION`. It does not replace the entire session. Keys not mentioned in the writes array are left untouched.
- **Flash writes replace all flash data.** Unlike session writes which merge key-by-key, flash writes replace the entire `$_SESSION['_flash']` array.
- **You cannot delete a session key** via the write-back pattern. The middleware only iterates over the writes array and sets values. To effectively "delete" a key, set it to `null`.
- **Do not write to `$_SESSION` directly** in your handlers. SessionMiddleware calls `session_write_close()` after reading write-back attributes, so any direct writes you made would persist, but they bypass the immutable contract and will confuse future maintainers.

---

### StaticFileMiddleware

#### What It Does

StaticFileMiddleware serves static files (CSS, JavaScript, images, fonts, etc.) directly from a directory on disk. If the request URI matches a real file inside the configured base directory, it returns the file contents with the appropriate `Content-Type` header. If the file does not exist or the path is suspicious (dotfiles, directory traversal), it passes the request through to `$next`.

#### Configuration

**Constructor:**

```php
StaticFileMiddleware::create(string $baseDir, array $mimeTypes = []): static
```

- `$baseDir` -- absolute path to the directory containing static files
- `$mimeTypes` -- optional associative array of extension-to-MIME-type mappings that are merged with the built-in defaults

**Configuration methods:**

```php
public function withBaseDir(string $baseDir): static
public function withMimeTypes(array $mimeTypes): static
```

`withMimeTypes()` merges new mappings into the existing set (including defaults).

**Default MIME types:**

| Extension | Content-Type |
|---|---|
| `html`, `htm` | `text/html` |
| `css` | `text/css` |
| `js` | `application/javascript` |
| `json` | `application/json` |
| `xml` | `application/xml` |
| `txt` | `text/plain` |
| `csv` | `text/csv` |
| `png` | `image/png` |
| `jpg`, `jpeg` | `image/jpeg` |
| `gif` | `image/gif` |
| `svg` | `image/svg+xml` |
| `ico` | `image/x-icon` |
| `webp` | `image/webp` |
| `pdf` | `application/pdf` |
| `woff` | `font/woff` |
| `woff2` | `font/woff2` |
| `ttf` | `font/ttf` |
| `eot` | `application/vnd.ms-fontobject` |
| `mp3` | `audio/mpeg` |
| `mp4` | `video/mp4` |
| `webm` | `video/webm` |
| `zip` | `application/zip` |

Unrecognized extensions fall back to `application/octet-stream`.

#### How to Use It

```php
use thegroovetrain\PiratePHP\StaticFileMiddleware;

$static = StaticFileMiddleware::create(__DIR__ . '/public')
    ->withMimeTypes([
        'wasm' => 'application/wasm',
        'avif' => 'image/avif',
    ]);

$app = App::create()
    ->withMiddleware($static)
    ->withRouter($router);
```

With this setup, a request to `/css/style.css` will serve the file at `./public/css/style.css` if it exists.

#### What It Reads from the Request

- `$request->getUri()` -- the URI path, used to locate the file on disk

#### What It Writes to the Response

When a file is found:
- `Content-Type` header set to the MIME type for the file extension
- `Content-Length` header set to the file size in bytes
- Body is a closure that calls `readfile()` for streaming output

When no file is found: passes through to `$next` without modification.

#### Edge Cases and Gotchas

- **Dotfile blocking:** Any URI segment starting with `.` causes the middleware to skip and pass through to `$next`. This protects `.htaccess`, `.env`, `.git/` and other sensitive hidden files. A request to `/assets/.secret/data.json` will not be served.
- **Directory traversal protection:** The middleware resolves the real path using `realpath()` and verifies it starts with the base directory's real path. Requests like `/../../etc/passwd` will not escape the base directory.
- **Directories are not served.** If the URI points to a directory (even with an `index.html` inside it), the middleware passes through. It only serves regular files.
- **Streaming body:** The response body is a closure, not a string. The file is read via `readfile()` when `Response::send()` is called. This means the file contents are not held in memory.
- **No caching headers.** StaticFileMiddleware does not set `Cache-Control`, `ETag`, or `Last-Modified` headers. Add those via a custom middleware wrapper if needed.
- **Base directory must exist.** If `realpath($baseDir)` returns `false`, every request will fall through to `$next`.

---

### RateLimitMiddleware

#### What It Does

RateLimitMiddleware limits the number of requests a single IP address can make within a time window. It uses file-based storage with advisory file locking, requiring no external dependencies like Redis or Memcached. When a client exceeds the limit, it returns a `429 Too Many Requests` response.

#### Configuration

**Constructor:**

```php
RateLimitMiddleware::create(int $maxRequests, int $windowSeconds, string $storagePath): static
```

- `$maxRequests` -- maximum number of requests allowed per window
- `$windowSeconds` -- duration of the rate limit window in seconds
- `$storagePath` -- directory path where per-IP counter files are stored

**Configuration methods:**

```php
public function withMaxRequests(int $maxRequests): static
public function withWindowSeconds(int $windowSeconds): static
public function withStoragePath(string $storagePath): static
```

#### How to Use It

```php
use thegroovetrain\PiratePHP\RateLimitMiddleware;

// Allow 100 requests per 60 seconds per IP
$rateLimit = RateLimitMiddleware::create(100, 60, '/tmp/pirate-ratelimit');

$app = App::create()
    ->withMiddleware($rateLimit)
    ->withRouter($router);
```

Stricter limits on a specific route:

```php
$apiRoute = Route::create()
    ->withPath('/api/expensive')
    ->withMethods('POST')
    ->withMiddleware(
        RateLimitMiddleware::create(10, 60, '/tmp/pirate-ratelimit-api')
    )
    ->withHandler($expensiveHandler);
```

#### What It Reads from the Request

- `$request->getServerData()` -- reads `REMOTE_ADDR` for the client IP address. Falls back to `'127.0.0.1'` if not set.

#### What It Writes to the Response

When the limit is exceeded: returns `Response::create()->withStatus(429)->withBody('Too Many Requests')`.

When within limits: passes the response from `$next` through unchanged. No rate limit headers (like `X-RateLimit-Remaining`) are added.

#### Edge Cases and Gotchas

- **Graceful degradation:** If the storage directory cannot be created, the file cannot be opened, or the file lock cannot be acquired, the middleware allows the request through. Storage failures never block legitimate traffic.
- **File locking:** Uses `LOCK_EX` (exclusive lock) to prevent race conditions between concurrent requests from the same IP. This is safe for single-server deployments.
- **Fixed window algorithm:** The rate limiter uses a fixed window, not sliding window. A burst of requests at the end of one window and the start of the next could allow up to `2 * maxRequests` in a short period.
- **IP identification:** The client IP comes from `REMOTE_ADDR`. Behind a reverse proxy, this will be the proxy's IP, not the client's. You would need a custom middleware that reads `X-Forwarded-For` and sets `REMOTE_ADDR` before RateLimitMiddleware runs.
- **Storage cleanup:** Counter files are never automatically deleted. Over time, the storage directory accumulates one file per unique IP (named by MD5 hash). You may want a cron job to periodically clean out old files.
- **No per-route awareness:** The count is global per IP. If you add RateLimitMiddleware at the app level, all routes share the same counter. Use different `$storagePath` values for different rate limit scopes.
- **The counter increments before the check.** The current request is counted before comparing against `maxRequests`. This means the limit is `maxRequests` inclusive -- the request that hits exactly `maxRequests` is allowed; the one at `maxRequests + 1` is rejected.

---

### ContentNegotiationMiddleware

#### What It Does

ContentNegotiationMiddleware inspects the `Accept` header and transforms structured data into the appropriate response format. Handlers return data by attaching it to the response as an attribute (via `ContentNegotiationMiddleware::ATTR_DATA`). The middleware then formats it as JSON or renders it through an HTML template, depending on what the client accepts.

#### Configuration

**Constructor:**

```php
ContentNegotiationMiddleware::create(RendererInterface $renderer, string $defaultTemplate): static
```

- `$renderer` -- an implementation of `RendererInterface` for HTML rendering
- `$defaultTemplate` -- the template name to use when rendering HTML

**Configuration methods:**

```php
public function withRenderer(RendererInterface $renderer): static
public function withDefaultTemplate(string $defaultTemplate): static
```

**Constants:**

| Constant | Value | Used On |
|---|---|---|
| `ContentNegotiationMiddleware::ATTR_DATA` | `'_pirate_data'` | Response attribute |

**RendererInterface:**

```php
interface RendererInterface
{
    public function render(string $template, array $data = []): string;
}
```

#### How to Use It

```php
use thegroovetrain\PiratePHP\ContentNegotiationMiddleware;
use thegroovetrain\PiratePHP\PhpRenderer;

$renderer = PhpRenderer::create(__DIR__ . '/templates');

$apiRouter = Router::create()
    ->withBasePath('/api')
    ->withMiddleware(
        ContentNegotiationMiddleware::create($renderer, 'default.php')
    )
    ->withRoute($usersRoute);
```

In your handler, attach data to the response instead of formatting it yourself:

```php
use thegroovetrain\PiratePHP\ContentNegotiationMiddleware;

$usersHandler = function (RequestInterface $request): ResponseInterface {
    $users = [
        ['id' => 1, 'name' => 'Blackbeard'],
        ['id' => 2, 'name' => 'Anne Bonny'],
    ];

    return Response::create()
        ->withAttribute(ContentNegotiationMiddleware::ATTR_DATA, $users);
};
```

A request with `Accept: application/json` gets JSON:

```json
[{"id":1,"name":"Blackbeard"},{"id":2,"name":"Anne Bonny"}]
```

A request without that header (or with `Accept: text/html`) gets the data rendered through the `default.php` template.

#### What It Reads from the Request

- `$request->getHeader('accept')` -- the Accept header, checked for `'application/json'`

#### What It Writes to the Response

If `ATTR_DATA` is present on the response:

- **JSON path:** Replaces the body with `json_encode($data)`, sets `Content-Type: application/json`. Preserves the original status code and all non-Content-Type headers.
- **HTML path:** Calls `$renderer->render($defaultTemplate, $data)`, replaces the body with the rendered HTML, sets `Content-Type: text/html`. If `$data` is not an array, it is wrapped as `['data' => $data]`. Preserves the original status code and all non-Content-Type headers.

If `ATTR_DATA` is not present: the response passes through unchanged.

#### Edge Cases and Gotchas

- **No ATTR_DATA, no transformation.** If your handler does not attach `ContentNegotiationMiddleware::ATTR_DATA` to the response, the middleware is a no-op. This means you can mix content-negotiated routes and manually-formatted routes under the same middleware.
- **JSON encoding errors** return a `500` response with the body `'JSON encoding error'`. Original headers are lost.
- **Template rendering errors** return a `500` response with the body `'Template rendering error'`. Original headers are lost.
- **Accept header matching is simple.** It checks `str_contains($accept, 'application/json')`. It does not parse quality values or handle `*/*`. If the Accept header contains `application/json` anywhere, you get JSON. Otherwise, you get HTML.
- **Non-array data for templates.** If `$data` is not an array (e.g., a string or an object), it is wrapped in `['data' => $data]` before being passed to the renderer. For JSON, any JSON-encodable value works as-is.
- **Original headers are preserved.** Headers from the original response (set by the handler) are copied to the new response, except for `Content-Type` which is overwritten by the middleware.
- **Typically route- or router-level.** This middleware makes most sense on API routers or specific routes, not as app-level middleware, since not every route returns structured data.

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
