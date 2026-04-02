# LoggingMiddleware

LoggingMiddleware records one log line per request containing the timestamp, HTTP method, URI, response status code, and elapsed time in milliseconds. It uses PiratePHP's `LoggerInterface`, so you can plug in any logger implementation. The built-in `FileLogger` writes to a file on disk.

## Configuration

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

FileLogger writes each message as one line to the specified file. It uses `FILE_APPEND | LOCK_EX` for atomic, concurrent-safe appends. Newline characters (`\n` and `\r`) in log messages are replaced with spaces to prevent log injection attacks. All write failures are silently swallowed -- FileLogger will never throw.

## Usage

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

**Log output format:**

```
[2026-03-31 14:22:05] GET /api/users 200 12ms
```

The format is: `[YYYY-MM-DD HH:MM:SS] METHOD URI STATUS TIMEms`

## What It Reads / What It Writes

**Reads from the request:**

- `$request->getMethod()` -- the HTTP method (e.g., `GET`, `POST`)
- `$request->getUri()` -- the request URI path

**Writes to the response:** Nothing. The response passes through unchanged.

## Edge Cases and Gotchas

- Logger failures are silently swallowed. If `$this->logger->log()` throws, the exception is caught and ignored. Your application will not crash because of a logging failure.
- Timing starts before `$next` is called and ends after the response is returned. The elapsed time includes all downstream middleware and the route handler.
- The `FileLogger` strips newlines from log messages to prevent log injection attacks. Both `\n` and `\r` are replaced with spaces.
- The `FileLogger` uses `LOCK_EX` for file writes, making it safe for concurrent requests.
- If `getMethod()` returns `null`, the log line shows `UNKNOWN`. If `getUri()` returns `null`, it shows `/`.

## Related

- [Middleware Overview](README.md) -- how the onion pipeline works, middleware ordering, and custom middleware examples
