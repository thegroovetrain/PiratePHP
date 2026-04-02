# Response

PiratePHP's `Response` class represents an HTTP response. It is immutable -- every `with*()` method returns a new instance. Responses carry a status code, headers, a body (string or callable), and optional attributes for communicating with middleware.

---

## Basic Response

```php
use thegroovetrain\PiratePHP\Response;

$response = Response::create()
    ->withStatus(200)
    ->withBody('Hello!')
    ->withHeader('Content-Type', 'text/plain');
```

The default status code is `200`, so you can skip `withStatus()` for success responses:

```php
$response = Response::create()
    ->withBody('Hello!')
    ->withHeader('Content-Type', 'text/plain');
```

---

## Status Codes

```php
$response = Response::create()->withStatus(404);
$response = Response::create()->withStatus(500, 'Custom Message');
```

- `withStatus(int $code, string $message = null): static` -- Sets the HTTP status code and an optional reason phrase. If no message is given, PiratePHP uses the standard reason phrase (e.g., "Not Found" for 404).
- `getStatusCode(): int` -- Returns the status code.
- `getStatusMessage(): string` -- Returns the reason phrase.

---

## Body

```php
$response = Response::create()->withBody('Hello, world!');
```

- `withBody(string|callable $content): static` -- Sets the response body. Accepts a string or a callable (see Callable Bodies below).
- `getBody(): string|\Closure` -- Returns the body.

---

## JSON Responses

The `json()` static factory encodes data and sets the `Content-Type` header:

```php
$response = Response::json(['name' => 'Blackbeard', 'crew' => 42]);
// Status: 200, Content-Type: application/json

$response = Response::json(['error' => 'Not Found'], 404);
// With a custom status code
```

If JSON encoding fails, `json()` returns a `500` response with the body `"JSON encoding error"`.

---

## Redirects

The `redirect()` static factory sets the `Location` header:

```php
$response = Response::redirect('/login');
// 302 redirect by default

$response = Response::redirect('/new-location', 301);
// Permanent redirect
```

---

## Callable Bodies (Streaming)

`withBody()` accepts a callable for deferred or streaming output:

```php
$response = Response::create()
    ->withBody(function () {
        readfile('/path/to/large-file.csv');
    })
    ->withHeader('Content-Type', 'text/csv');
```

The callable is invoked when `send()` is called, not when the response is built. This is useful for serving large files without loading them entirely into memory.

---

## Headers

### Setting Headers

```php
// Replace a header (overwrites any previous value)
$response = $response->withHeader('X-Custom', 'value');

// Append a value to a header (useful for Set-Cookie)
$response = $response->withAddedHeader('Set-Cookie', 'name=value; Path=/');

// Set multiple headers at once
$response = $response->withHeaders([
    'X-One' => 'first',
    'X-Two' => 'second',
]);
```

- `withHeader(string $name, string $value): static` -- Sets a header, replacing any existing values.
- `withAddedHeader(string $name, string $value): static` -- Appends a value to the header. Essential for headers like `Set-Cookie` where multiple values are valid. See [Cookies](cookies.md) for details.
- `withHeaders(array $headers): static` -- Sets multiple headers. Values can be strings or arrays of strings.

### Removing Headers

```php
$response = $response->withoutHeaders('X-Custom', 'X-One');
```

- `withoutHeaders(string ...$names): static` -- Removes the specified headers.

### Reading Headers

```php
$type = $response->getHeader('Content-Type');       // First value, or null
$cookies = $response->getHeaderArray('Set-Cookie');  // All values as array
$all = $response->getHeaders();                      // All headers
```

- `getHeader(string $name): mixed` -- Returns the first value for the header, or `null` if not set.
- `getHeaderArray(string $name): array` -- Returns all values for the header as an array. Returns an empty array if the header is not set.
- `getHeaders(): array` -- Returns all headers as an associative array where values are arrays of strings.

---

## Sessions and Flash Messages

Attach session data and flash messages to the response. `Response::send()` writes them back to `$_SESSION` before emitting headers and body.

```php
// Write session data
$session = $request->getSession()
    ->with('username', 'blackbeard')
    ->with('user_id', 42);

$response = Response::create()
    ->withBody('Logged in!')
    ->withSession($session);

// Write flash messages for the next request
$response = Response::redirect('/dashboard')
    ->withFlash(['success' => 'Welcome aboard!']);

// Combine both
$response = Response::redirect('/dashboard')
    ->withSession($session)
    ->withFlash(['success' => 'Welcome aboard!']);
```

- `withSession(SessionInterface $session): static` -- Returns a new response with session data to persist. When `send()` is called, the session's data replaces `$_SESSION`.
- `getSession(): ?SessionInterface` -- Returns the attached session, or `null` if none was set.
- `withFlash(array $data): static` -- Returns a new response with flash data for the next request. When `send()` is called, the data is written to `$_SESSION['_flash']`.
- `getFlashData(): ?array` -- Returns the attached flash data array, or `null` if none was set.

If you do not call `withSession()`, the existing `$_SESSION` data is left unchanged. See [Sessions & Flash Messages](sessions.md) for the full session lifecycle.

---

## Response Attributes

Responses have attributes just like requests. This is how handlers communicate data back to middleware (for example, content negotiation):

```php
$response = $response->withAttribute('key', 'value');
$value = $response->getAttribute('key');
```

- `withAttribute(string $key, mixed $value): static` -- Returns a new response with the attribute set.
- `getAttribute(string $key): mixed` -- Returns the attribute value, or `null` if not set.

---

## Related Pages

- [Getting Started](getting-started.md) -- The handler contract and how responses fit in
- [Request](request.md) -- The request object your handler receives
- [Sessions & Flash Messages](sessions.md) -- Session lifecycle and flash messages
- [Cookies](cookies.md) -- Writing cookies via `withAddedHeader('Set-Cookie', ...)`
- [Templates](templates.md) -- Rendering HTML to use as the response body
