# Cookies

PiratePHP provides the `CookieJar` class for reading cookies from the request. Writing cookies is done via response headers using the standard `Set-Cookie` header.

---

## Reading Cookies

Parse cookies from the request's `Cookie` header using `CookieJar::fromHeaderString()`:

```php
use thegroovetrain\PiratePHP\CookieJar;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\ResponseInterface;

$handler = function (RequestInterface $request): ResponseInterface {
    $cookieHeader = $request->getHeader('cookie') ?? '';
    $cookies = CookieJar::fromHeaderString($cookieHeader);

    $theme = $cookies->get('theme', 'light');
    $hasToken = $cookies->has('auth_token');
    $all = $cookies->all();

    return Response::create()->withBody("Theme: {$theme}");
};
```

### CookieJar API

- `CookieJar::fromHeaderString(string $cookieHeader): static` -- Parses a `Cookie` header string (e.g., `"theme=dark; lang=en"`) into a `CookieJar`. Values are URL-decoded automatically.
- `CookieJar::create(): static` -- Creates an empty `CookieJar`.
- `get(string $name, ?string $default = null): ?string` -- Returns the cookie value, or the default if the cookie is not present.
- `has(string $name): bool` -- Returns `true` if the cookie exists.
- `all(): array` -- Returns all cookies as an associative array.

---

## Writing Cookies

Set cookies by adding `Set-Cookie` headers to the response. Use `withAddedHeader()` so multiple cookies do not overwrite each other:

```php
$handler = function (RequestInterface $request): ResponseInterface {
    return Response::create()
        ->withBody('Cookies set!')
        ->withAddedHeader('Set-Cookie', 'theme=dark; Path=/; HttpOnly; SameSite=Lax')
        ->withAddedHeader('Set-Cookie', 'lang=en; Path=/; HttpOnly; SameSite=Lax');
};
```

Note the use of `withAddedHeader()` rather than `withHeader()`. The `withHeader()` method replaces all values for that header name, so using it for multiple cookies would only keep the last one. `withAddedHeader()` appends each value. For details, see [Response](response.md).

---

## Cookie Options

Include standard cookie attributes in the header string:

| Attribute   | Example                                    | Purpose                              |
|-------------|--------------------------------------------|--------------------------------------|
| `Path`      | `Path=/`                                   | Cookie scope within the domain       |
| `Domain`    | `Domain=example.com`                       | Cookie scope across subdomains       |
| `Expires`   | `Expires=Thu, 01 Jan 2027 00:00:00 GMT`    | Absolute expiration date             |
| `Max-Age`   | `Max-Age=3600`                             | Seconds until expiration             |
| `Secure`    | `Secure`                                   | Only send over HTTPS                 |
| `HttpOnly`  | `HttpOnly`                                 | Not accessible via JavaScript        |
| `SameSite`  | `SameSite=Lax`                             | CSRF protection (Lax, Strict, None)  |

Example with multiple attributes:

```php
$response = $response->withAddedHeader(
    'Set-Cookie',
    'session=abc123; Path=/; HttpOnly; Secure; SameSite=Strict; Max-Age=3600'
);
```

---

## Deleting Cookies

To delete a cookie, set it with an expired date:

```php
$response = $response->withAddedHeader(
    'Set-Cookie',
    'theme=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT'
);
```

---

## Related Pages

- [Request](request.md) -- Reading the `Cookie` header and other request data
- [Response](response.md) -- The `withAddedHeader()` method for setting multiple cookies
- [Getting Started](getting-started.md) -- Installation and overview
