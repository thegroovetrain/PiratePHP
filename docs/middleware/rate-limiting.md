# RateLimitMiddleware

RateLimitMiddleware limits the number of requests a single IP address can make within a time window. It uses file-based storage with advisory file locking, requiring no external dependencies like Redis or Memcached. When a client exceeds the limit, it returns a `429 Too Many Requests` response.

## Configuration

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

## Usage

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

## What It Reads / What It Writes

**Reads from the request:**

- `$request->getServerData()` -- reads `REMOTE_ADDR` for the client IP address. Falls back to `'127.0.0.1'` if not set.

**Writes to the response:**

- When the limit is exceeded: returns `Response::create()->withStatus(429)->withBody('Too Many Requests')`.
- When within limits: passes the response from `$next` through unchanged. No rate limit headers (like `X-RateLimit-Remaining`) are added.

## How It Works Internally

Each IP address gets a JSON file in the storage directory, named by the MD5 hash of the IP. The file contains a `window_start` timestamp and a `count`. On each request:

1. The storage directory is created if it does not exist (`mkdir` with `0755`).
2. The per-IP file is opened with `fopen($file, 'c+')`.
3. An exclusive lock is acquired with `flock($handle, LOCK_EX)`.
4. If the current time minus `window_start` exceeds `windowSeconds`, the window resets to now with count 0.
5. The count is incremented.
6. The file is truncated and rewritten with the updated data.
7. The lock is released.
8. If `count > maxRequests`, a 429 response is returned.

## Edge Cases and Gotchas

- **Graceful degradation:** If the storage directory cannot be created, the file cannot be opened, or the file lock cannot be acquired, the middleware allows the request through. Storage failures never block legitimate traffic.
- **File locking (flock atomicity):** Uses `LOCK_EX` (exclusive lock) to prevent race conditions between concurrent requests from the same IP. This is safe for single-server deployments.
- **Fixed window reset:** The rate limiter uses a fixed window, not sliding window. A burst of requests at the end of one window and the start of the next could allow up to `2 * maxRequests` in a short period.
- **Per-IP keying via REMOTE_ADDR:** The client IP comes from `REMOTE_ADDR` in the server data. Behind a reverse proxy, this will be the proxy's IP, not the client's. You would need a custom middleware that reads `X-Forwarded-For` and sets `REMOTE_ADDR` before RateLimitMiddleware runs.
- **Storage cleanup:** Counter files are never automatically deleted. Over time, the storage directory accumulates one file per unique IP (named by MD5 hash). You may want a cron job to periodically clean out old files.
- **No per-route awareness:** The count is global per IP. If you add RateLimitMiddleware at the app level, all routes share the same counter. Use different `$storagePath` values for different rate limit scopes.
- **The counter increments before the check.** The current request is counted before comparing against `maxRequests`. This means the limit is `maxRequests` inclusive -- the request that hits exactly `maxRequests` is allowed; the one at `maxRequests + 1` is rejected.

## Related

- [Middleware Overview](README.md) -- how the onion pipeline works, middleware ordering, and custom middleware examples
