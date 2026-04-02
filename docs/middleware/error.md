# ErrorMiddleware

ErrorMiddleware wraps the entire downstream pipeline in a try/catch. If any middleware or route handler throws a `\Throwable`, it catches the exception and returns a clean error response instead of letting PHP crash. You can provide a custom error handler to control the response format, log the error, or render a custom error page. If no handler is set (or the handler itself throws), it returns a plain-text `500 Internal Server Error`.

## Configuration

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

## Usage

Basic usage with the default 500 response:

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

## What It Reads / What It Writes

**Reads from the request:** The request is passed through to `$next` unchanged. If a custom error handler is configured, the original request is forwarded to the handler as its second argument.

**Writes to the response:**

- On success: nothing. The downstream response passes through untouched.
- On error without a custom handler: returns `Response::create()->withStatus(500)->withBody('Internal Server Error')` with `Content-Type: text/plain`.
- On error when the custom handler also throws: returns the same default 500 response.

## Edge Cases and Gotchas

- If your custom error handler itself throws an exception, ErrorMiddleware catches that too and falls back to the default plain-text 500 response. You will not see the handler's exception -- it is silently swallowed.
- ErrorMiddleware catches `\Throwable`, not just `\Exception`. This includes `TypeError`, `Error`, `ParseError`, and any other throwable.
- Place ErrorMiddleware as the **outermost** middleware. If another middleware outside it throws, the error will not be caught.

## Related

- [Middleware Overview](README.md) -- how the onion pipeline works, middleware ordering, and custom middleware examples
