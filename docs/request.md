# Request

PiratePHP's `Request` class wraps incoming HTTP data into an immutable object. In your handlers, you receive a `RequestInterface` with methods for reading the URI, method, headers, query parameters, POST data, JSON bodies, and custom attributes.

---

## URI and Method

```php
$uri = $request->getUri();       // "/user/42" (query string stripped, path normalized)
$method = $request->getMethod(); // "GET", "POST", "PUT", "DELETE", etc.
```

`getUri()` returns the request path with the query string removed. The path is normalized (double slashes collapsed, trailing slashes removed except for the root `/`).

`getMethod()` returns the HTTP method from `$_SERVER['REQUEST_METHOD']`.

---

## Query Parameters

For a URL like `/search?q=pirate&page=2`:

```php
$q = $request->getQueryParam('q');              // "pirate"
$page = $request->getQueryParam('page', '1');   // "2" (or "1" as default)
$all = $request->getQueryParams();              // ['q' => 'pirate', 'page' => '2']
```

- `getQueryParam(string $key, string|null $default = null): mixed` -- Returns a single query parameter, or the default if missing.
- `getQueryParams(): array` -- Returns all query parameters as an associative array.

---

## POST Data

For form submissions (`application/x-www-form-urlencoded` or `multipart/form-data`):

```php
$name = $request->getPostDatum('name');
$email = $request->getPostDatum('email', 'none');
$all = $request->getPostData();
```

- `getPostDatum(string $key, string|null $default = null): mixed` -- Returns a single POST field, or the default if missing.
- `getPostData(): array` -- Returns all POST data as an associative array.

---

## JSON Bodies

For `application/json` request bodies, use `getParsedBody()`:

```php
$data = $request->getParsedBody();
// Returns the decoded associative array, or null if decoding failed
```

`getParsedBody()` only decodes when the `Content-Type` header contains `application/json`. The result is cached -- calling it twice does not re-decode. If the JSON is invalid, it returns `null`.

---

## Raw Body

```php
$raw = $request->getRawBody(); // The raw string from php://input
```

Use this for content types that PiratePHP does not parse automatically (XML, plain text, binary data, etc.).

---

## Headers

```php
$contentType = $request->getHeader('Content-Type');  // Case-insensitive
$auth = $request->getHeader('Authorization');         // Case-insensitive
$all = $request->getHeaders();                        // All headers (keys lowercased)
```

- `getHeader(string $key, string|null $default = null): mixed` -- Returns a header value by name. Lookups are case-insensitive; all header keys are stored lowercased internally.
- `getHeaders(): array` -- Returns all headers as an associative array with lowercased keys.

---

## Server Data

Access raw `$_SERVER` values:

```php
$ip = $request->getServerDatum('REMOTE_ADDR');
$host = $request->getServerDatum('HTTP_HOST');
$all = $request->getServerData();
```

- `getServerDatum(string $key, string|null $default = null): mixed` -- Returns a single server variable, or the default if missing.
- `getServerData(): array` -- Returns the entire server data array.

---

## Attributes

Attributes are a general-purpose key-value store for passing data through the middleware and handler pipeline:

```php
$request = $request->withAttribute('user', $currentUser);
$user = $request->getAttribute('user');
$request = $request->withoutAttribute('user');
```

- `withAttribute(string $key, mixed $value): static` -- Returns a new request with the attribute set.
- `getAttribute(string $key): mixed` -- Returns the attribute value, or `null` if not set.
- `withoutAttribute(string ...$keys): static` -- Returns a new request with the specified attributes removed.

Route parameters (`:id`, `:name`, etc.) are automatically attached as attributes by the router. See [Routing](routing.md) for details on dynamic parameters.

---

## Sessions and Flash Messages

Access session data and flash messages directly from the request:

```php
$session = $request->getSession();
$flash = $request->getFlash();

$username = $session->get('username', 'Guest');
$notice = $flash->get('notice');
```

- `getSession(): SessionInterface` -- Returns the current session data as an immutable `PhpSession` object.
- `getFlash(): SessionInterface` -- Returns flash data from the previous request as an immutable `PhpSession` object.

Both return objects with `get()`, `has()`, and `all()` methods for reading, and `with()` and `without()` methods for building updated sessions.

To write session data, build an updated session using `with()` / `without()` and attach it to the response. See [Sessions & Flash Messages](sessions.md) for the full pattern.

---

## Creating Requests for Testing

`Request::createFromArrays()` lets you build request objects without relying on PHP superglobals. This is essential for testing:

```php
use thegroovetrain\PiratePHP\Request;

$request = Request::createFromArrays(
    query: ['page' => '2'],
    post: [],
    server: ['REQUEST_URI' => '/search', 'REQUEST_METHOD' => 'GET'],
    headers: ['Accept' => 'application/json'],
    body: ''
);
```

All parameters are optional and default to empty arrays/strings:

```php
public static function createFromArrays(
    array $query = [],
    array $post = [],
    array $server = [],
    array $headers = [],
    string $body = '',
    array $session = [],
    array $flash = []
): static
```

The `session` and `flash` parameters let you inject session and flash data for testing without starting a real PHP session.

For more testing patterns, see [Testing](testing.md).

---

## Related Pages

- [Getting Started](getting-started.md) -- The handler contract and how requests flow through the app
- [Routing](routing.md) -- How route parameters become request attributes
- [Response](response.md) -- Building the response your handler returns
- [Sessions & Flash Messages](sessions.md) -- Reading and writing session data
- [Testing](testing.md) -- Creating test requests with `createFromArrays()`
