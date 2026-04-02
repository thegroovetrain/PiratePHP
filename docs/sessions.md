# Sessions & Flash Messages

Sessions are first-class citizens in PiratePHP. They are part of the Request/Response lifecycle -- not middleware. The `Request` object carries session data inward, and the `Response` object carries session writes back out. `Response::send()` persists changes to `$_SESSION` and closes the session.

## Table of Contents

- [How Sessions Work](#how-sessions-work)
- [Reading Session Data](#reading-session-data)
- [Writing Session Data](#writing-session-data)
- [PhpSession with() and without()](#phpsession-with-and-without)
- [Flash Messages](#flash-messages)
- [Session Lifecycle](#session-lifecycle)
- [Secure Defaults](#secure-defaults)
- [Testing Sessions](#testing-sessions)
- [Edge Cases and Gotchas](#edge-cases-and-gotchas)

---

## How Sessions Work

When `Request::create()` is called (which happens automatically inside `App::run()`), PiratePHP:

1. Starts a PHP session with secure cookie defaults (if not already started).
2. Reads `$_SESSION` into an immutable `PhpSession` object available via `$request->getSession()`.
3. Reads `$_SESSION['_flash']` into a separate `PhpSession` object available via `$request->getFlash()`.
4. Deletes `$_SESSION['_flash']` so flash data is one-time-use.

When `Response::send()` is called, PiratePHP:

1. If a session was attached via `$response->withSession()`, writes its data back to `$_SESSION`.
2. If flash data was attached via `$response->withFlash()`, writes it to `$_SESSION['_flash']`.
3. Calls `session_write_close()`.

This keeps the session lifecycle inside the Request/Response objects without any middleware.

---

## Reading Session Data

Use `$request->getSession()` to access the current session and `$request->getFlash()` for flash messages from the previous request:

```php
$handler = function (RequestInterface $request): ResponseInterface {
    $session = $request->getSession();
    $flash = $request->getFlash();

    $username = $session->get('username', 'Guest');
    $message = $flash->get('success_message');

    return Response::create()
        ->withBody("Hello, {$username}! {$message}");
};
```

Both `getSession()` and `getFlash()` return a `SessionInterface` object with these methods:

```php
$session->get(string $key, mixed $default = null): mixed
$session->has(string $key): bool
$session->all(): array
```

---

## Writing Session Data

To persist session changes, use `PhpSession::with()` and `PhpSession::without()` to build an updated session, then attach it to the response with `withSession()`:

```php
$loginHandler = function (RequestInterface $request): ResponseInterface {
    // ... validate credentials ...

    $session = $request->getSession()
        ->with('username', 'blackbeard')
        ->with('user_id', 42);

    return Response::redirect('/dashboard')
        ->withSession($session);
};
```

The session data is written back to `$_SESSION` when `Response::send()` is called.

---

## PhpSession with() and without()

`PhpSession` is immutable. The `with()` and `without()` methods return new instances:

```php
$session = $request->getSession();

// Add or update a key
$session = $session->with('cart_count', 3);
$session = $session->with('last_page', '/products');

// Remove a key
$session = $session->without('temporary_token');

// Chain operations
$session = $request->getSession()
    ->with('username', 'blackbeard')
    ->with('role', 'captain')
    ->without('guest_id');

// Attach the modified session to the response
return Response::create()
    ->withBody('Updated!')
    ->withSession($session);
```

The `with()` and `without()` methods follow the same immutable clone pattern as everything else in PiratePHP.

---

## Flash Messages

Flash data survives for exactly one request. Write flash data on one request, read it on the next, and it is gone after that.

**Writing flash data (request 1):**

```php
// In a POST handler -- write flash and redirect
return Response::redirect('/dashboard')
    ->withFlash([
        'notice' => 'Profile updated successfully.',
    ]);
```

**Reading flash data (request 2):**

```php
// In the GET handler after the redirect
$flash = $request->getFlash();
$notice = $flash->get('notice'); // "Profile updated successfully."
```

**Flash data is gone (request 3):**

```php
$flash = $request->getFlash();
$notice = $flash->get('notice'); // null
```

Flash data is ideal for success/error messages after a redirect (the Post/Redirect/Get pattern).

You can also combine session writes and flash writes on the same response:

```php
$session = $request->getSession()->with('username', 'blackbeard');

return Response::redirect('/dashboard')
    ->withSession($session)
    ->withFlash(['success' => 'Welcome aboard, Captain!']);
```

---

## Session Lifecycle

```
 1. Request::create() is called
        |
 2. session_start() with secure cookie defaults
        |
 3. Read $_SESSION into PhpSession object
        |
 4. Read $_SESSION['_flash'] into separate PhpSession object
        |
 5. Delete $_SESSION['_flash'] (flash data is one-time-use)
        |
 6. Request is created with session and flash:
    - $request->getSession()  => session PhpSession
    - $request->getFlash()    => flash PhpSession
        |
 7. Request flows through middleware and handlers
        |
        |   ... handler reads from $request->getSession() ...
        |   ... handler builds updated session with ->with() / ->without() ...
        |   ... handler attaches session to response with ->withSession() ...
        |   ... handler attaches flash to response with ->withFlash() ...
        |
 8. Response::send() is called
        |
 9. If $response->getSession() is not null:
    write session data to $_SESSION
        |
10. If $response->getFlashData() is not null:
    write flash data to $_SESSION['_flash']
        |
11. session_write_close()
        |
12. Emit headers and body
```

---

## Secure Defaults

`Request::create()` configures session cookies with secure defaults:

- `httponly => true` -- JavaScript cannot access the session cookie, preventing XSS-based session theft.
- `samesite => 'Lax'` -- The cookie is not sent on cross-origin POST requests, providing baseline CSRF protection.
- `secure` -- Automatically set to `true` when `$_SERVER['HTTPS']` is present. The cookie is only sent over encrypted connections.

These defaults are set before `session_start()` is called. There is no configuration method to override them -- they are always applied.

---

## Testing Sessions

Use `Request::createFromArrays()` with the `session:` and `flash:` parameters to test handlers that read or write session data:

**Testing session reads:**

```php
$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/dashboard', 'REQUEST_METHOD' => 'GET'],
    session: ['username' => 'blackbeard', 'user_id' => 42],
    flash: ['notice' => 'Welcome back!']
);

$session = $request->getSession();
assert($session->get('username') === 'blackbeard');

$flash = $request->getFlash();
assert($flash->get('notice') === 'Welcome back!');
```

**Testing session writes:**

```php
$handler = function (RequestInterface $request): ResponseInterface {
    $session = $request->getSession()->with('visits', 1);
    return Response::create()
        ->withBody('OK')
        ->withSession($session);
};

$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/home', 'REQUEST_METHOD' => 'GET'],
    session: []
);

$response = $handler($request);
$updatedSession = $response->getSession();
assert($updatedSession->get('visits') === 1);
```

**Testing flash writes:**

```php
$handler = function (RequestInterface $request): ResponseInterface {
    return Response::redirect('/dashboard')
        ->withFlash(['success' => 'Saved!']);
};

$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/form', 'REQUEST_METHOD' => 'POST']
);

$response = $handler($request);
assert($response->getFlashData() === ['success' => 'Saved!']);
```

---

## Edge Cases and Gotchas

- **Session reads are a snapshot.** The `PhpSession` from `$request->getSession()` reflects the state at the start of the request. If you build an updated session with `->with()`, those changes are not visible in the original `$request->getSession()` object.
- **withSession() replaces the entire session.** When `Response::send()` writes session data, it sets `$_SESSION` to the full contents of the session object. Make sure to start from `$request->getSession()` and use `->with()` / `->without()` to preserve existing keys.
- **withFlash() replaces all flash data.** The array passed to `withFlash()` becomes the entire `$_SESSION['_flash']` for the next request.
- **Session is only written if attached.** If you do not call `$response->withSession()`, the session is not modified. Existing `$_SESSION` data persists unchanged.
- **Do not write to `$_SESSION` directly** in your handlers. `Response::send()` calls `session_write_close()` after writing, so any direct writes would persist, but they bypass the immutable contract and will confuse future maintainers.

---

## Related

- [Request](request.md) -- `getSession()` and `getFlash()` methods
- [Response](response.md) -- `withSession()`, `getSession()`, `withFlash()`, `getFlashData()` methods
- [Testing](testing.md) -- Testing with `createFromArrays(session:, flash:)`
- [Architecture](architecture.md) -- Session design philosophy
