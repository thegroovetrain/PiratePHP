# SessionMiddleware

SessionMiddleware manages PHP sessions within PiratePHP's immutable architecture. It starts the session, reads existing session data and flash data into read-only `PhpSession` objects, attaches them to the request as attributes, calls the downstream handler, reads write-back instructions from the response attributes, persists changes to `$_SESSION`, and closes the session. This is the bridge between PHP's mutable session mechanism and PiratePHP's immutable request/response objects.

## Configuration

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

**PhpSession (read-only session object):**

```php
$session->get(string $key, mixed $default = null): mixed
$session->has(string $key): bool
$session->all(): array
```

There is no `set()` method. You cannot modify it. This is by design.

## Usage

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

**Flash messages across requests:**

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

## The Immutable Write-Back Pattern

This is the most important concept in PiratePHP's session handling.

**The problem:** PHP sessions are inherently mutable -- you write to `$_SESSION` directly. But PiratePHP's requests and responses are immutable. A handler cannot mutate the request it received, and it cannot reach out and modify `$_SESSION` directly (well, it *could*, but that would break the immutable contract).

**The solution:** SessionMiddleware uses a two-phase approach. Session *reads* flow in through request attributes. Session *writes* flow out through response attributes. The middleware itself is the only code that touches `$_SESSION`.

### Lifecycle Diagram

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

**Reading session data:** The `PhpSession` object attached to the request is read-only. It implements `SessionInterface` with `get()`, `has()`, and `all()` methods. There is no `set()` -- you cannot modify it.

**Writing session data:** To write to the session, attach an associative array to the *response* using `SessionMiddleware::ATTR_SESSION_WRITES`. Each key/value pair in the array will be written to `$_SESSION` after the handler returns.

```php
// This tells SessionMiddleware to write these values
$response = $response->withAttribute(SessionMiddleware::ATTR_SESSION_WRITES, [
    'cart_count' => 3,
    'last_page' => '/products',
]);
```

**Flash data:** Flash data survives for exactly one request. When you write flash data, it is stored in `$_SESSION['_flash']`. On the *next* request, SessionMiddleware reads it out, attaches it to the request as `SessionMiddleware::ATTR_FLASH`, and then immediately deletes it from `$_SESSION`. Flash data is perfect for success/error messages after a redirect.

## What It Reads / What It Writes

**Reads from the request:**

- `$request->getServerData()` -- checks for `HTTPS` key to auto-set the `secure` cookie flag

**Writes to the request (before passing downstream):**

- `SessionMiddleware::ATTR_SESSION` -- a `PhpSession` object with the current session data
- `SessionMiddleware::ATTR_FLASH` -- a `PhpSession` object with flash data from the previous request

**Reads from the response (after downstream returns):**

- `SessionMiddleware::ATTR_SESSION_WRITES` -- associative array of key/value pairs to write to `$_SESSION`
- `SessionMiddleware::ATTR_FLASH_WRITES` -- associative array to store as `$_SESSION['_flash']`

**Writes to the response:** Nothing. SessionMiddleware does not add or modify response headers or body. The session cookie is set by PHP's native `session_start()`.

## Edge Cases and Gotchas

- **Session start failure:** If `session_start()` throws (e.g., headers already sent), SessionMiddleware catches the exception and calls `$next($request)` without attaching any session attributes. Your handler should check that `$request->getAttribute(SessionMiddleware::ATTR_SESSION)` is not `null` before calling methods on it.
- **HTTPS auto-detection:** The `secure` cookie parameter is automatically set to `true` if the `HTTPS` key exists in the server data, overriding whatever you configured via `withCookieParams()`.
- **Write-back is not a merge with existing session.** `SessionMiddleware::ATTR_SESSION_WRITES` writes individual keys into `$_SESSION`. It does not replace the entire session. Keys not mentioned in the writes array are left untouched.
- **Flash writes replace all flash data.** Unlike session writes which merge key-by-key, flash writes replace the entire `$_SESSION['_flash']` array.
- **You cannot delete a session key** via the write-back pattern. The middleware only iterates over the writes array and sets values. To effectively "delete" a key, set it to `null`.
- **Do not write to `$_SESSION` directly** in your handlers. SessionMiddleware calls `session_write_close()` after reading write-back attributes, so any direct writes you made would persist, but they bypass the immutable contract and will confuse future maintainers.

## Related

- [Middleware Overview](README.md) -- how the onion pipeline works, middleware ordering, and custom middleware examples
