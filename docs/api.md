# PiratePHP API Reference

Namespace: `thegroovetrain\PiratePHP`

All classes follow an immutable pattern. Every `with*()` method returns a **new instance** (clone); the original is never modified.

---

## 1. App

### `AppInterface`

Interface for the application entry point.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(): static` | Factory; creates a new App instance |
| `withRouter` | `public function withRouter(RouterInterface $router): static` | Returns clone with the given router added |

### `App`

Main application class. Implements `AppInterface`. Uses trait **`HasMiddleware`**.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(): static` | Factory; creates a new App |
| `run` | `public function run(): void` | Creates a Request from globals, dispatches through middleware/routers, and sends the Response |
| `withRouter` | `public function withRouter(RouterInterface ...$router): static` | Returns clone with one or more routers appended |
| `getRouters` | `public function getRouters(): array` | Returns the array of registered routers |
| `getSortedRouters` | `public function getSortedRouters(): array` | Returns routers sorted by base path length (longest first) |

Via **HasMiddleware** trait: `withMiddleware`, `getMiddleware`, `handle`. See [HasMiddleware](#hasmiddleware).

---

## 2. Router

### `RouterInterface`

Interface for routers.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(): static` | Factory; creates a new Router |
| `withBasePath` | `public function withBasePath(string $basepath): static` | Returns clone with the given base path |
| `withRoute` | `public function withRoute(RouteInterface ...$route): static` | Returns clone with one or more routes appended |
| `getBasePath` | `public function getBasePath(): string` | Returns the base path |
| `getRoutes` | `public function getRoutes(): array` | Returns the array of routes |
| `urlFor` | `public function urlFor(string $name, array $params = []): string` | Generates a URL for a named route, replacing `:param` placeholders. Throws `\RuntimeException` if name not found |

### `Router`

Default router implementation. Implements `RouterInterface`. Uses traits **`HasMiddleware`**, **`HasNormalizeUriPath`**.

All methods from `RouterInterface` above, plus:

Via **HasMiddleware** trait: `withMiddleware`, `getMiddleware`, `handle`. See [HasMiddleware](#hasmiddleware).

Routing behavior:
- Routes are matched by converting `:param` segments to named regex capture groups.
- Matched params are attached to the Request as attributes.
- Returns 404 if no path matches, 405 if path matches but method does not.

---

## 3. Route

### `RouteInterface`

Interface for individual routes.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(): static` | Factory; creates a new Route |
| `withPath` | `public function withPath(string $path): static` | Returns clone with the given path appended |
| `withHandler` | `public function withHandler(callable $handler): static` | Returns clone with the given request handler |
| `withMethods` | `public function withMethods(string ...$methods): static` | Returns clone with one or more HTTP methods appended |
| `withMiddleware` | `public function withMiddleware(callable ...$middleware): static` | Returns clone with middleware appended |
| `withName` | `public function withName(string $name): static` | Returns clone with the given name (used by `Router::urlFor`) |
| `getPath` | `public function getPath(): string` | Returns the route path |
| `getHandler` | `public function getHandler(): mixed` | Returns the handler callable, or null |
| `getMethods` | `public function getMethods(): array` | Returns the array of HTTP method strings |
| `getMiddleware` | `public function getMiddleware(): array` | Returns the array of middleware callables |
| `getName` | `public function getName(): string\|null` | Returns the route name, or null |

### `Route`

Default route implementation. Implements `RouteInterface`. Uses traits **`HasMiddleware`**, **`HasNormalizeUriPath`**.

All methods from `RouteInterface` above.

Via **HasMiddleware** trait: `handle`. See [HasMiddleware](#hasmiddleware).

Path parameters use the `:paramName` syntax. `withPath` validates parameter names against `/^[a-zA-Z_]\w*$/` and throws `\InvalidArgumentException` on invalid names. Successive `withPath` calls append to the existing path.

#### HTTP method constants (on `Request`)

Use these with `withMethods`:

```php
Request::HTTP_CONNECT  // 'CONNECT'
Request::HTTP_DELETE   // 'DELETE'
Request::HTTP_GET      // 'GET'
Request::HTTP_HEAD     // 'HEAD'
Request::HTTP_OPTIONS  // 'OPTIONS'
Request::HTTP_PATCH    // 'PATCH'
Request::HTTP_POST     // 'POST'
Request::HTTP_PUT      // 'PUT'
Request::HTTP_TRACE    // 'TRACE'
```

---

## 4. Request

### `RequestInterface`

Interface for immutable HTTP request objects.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(): static` | Factory; builds from PHP superglobals (`$_GET`, `$_POST`, `$_SERVER`, `php://input`) |
| `createFromArrays` | `public static function createFromArrays(array $query = [], array $post = [], array $server = [], array $headers = [], string $body = ''): static` | Factory; builds from explicit arrays (useful for testing) |
| `withAttribute` | `public function withAttribute(string $key, mixed $value): static` | Returns clone with the given attribute set |
| `withoutAttribute` | `public function withoutAttribute(string ...$keys): static` | Returns clone without the named attributes |
| `getAttribute` | `public function getAttribute(string $key): mixed` | Returns the attribute value, or null |
| `getQueryParams` | `public function getQueryParams(): array` | Returns all query parameters |
| `getQueryParam` | `public function getQueryParam(string $key, string\|null $default = null): mixed` | Returns a single query parameter, or `$default` |
| `getPostData` | `public function getPostData(): array` | Returns all POST data |
| `getPostDatum` | `public function getPostDatum(string $key, string\|null $default = null): mixed` | Returns a single POST value, or `$default` |
| `getServerData` | `public function getServerData(): array` | Returns all server data |
| `getServerDatum` | `public function getServerDatum(string $key, string\|null $default = null): mixed` | Returns a single server value, or `$default` |
| `getHeaders` | `public function getHeaders(): array` | Returns all request headers (keys are lowercase) |
| `getHeader` | `public function getHeader(string $key, string\|null $default = null): mixed` | Returns a single header value (case-insensitive lookup), or `$default` |
| `getUri` | `public function getUri(): mixed` | Returns the request URI path (query string stripped, normalized) |
| `getMethod` | `public function getMethod(): mixed` | Returns the HTTP method string |
| `getRawBody` | `public function getRawBody(): string` | Returns the raw request body |
| `getParsedBody` | `public function getParsedBody(): mixed` | Returns the parsed body (JSON-decoded if Content-Type is `application/json`; cached) |

### `Request`

Default implementation. Implements `RequestInterface`. Uses traits **`HasAttributes`**, **`HasNormalizeUriPath`**.

### `HasAttributes`

Trait used by **`Request`** and **`Response`**. Provides an arbitrary key-value attribute bag.

| Method | Signature | Description |
|--------|-----------|-------------|
| `withAttribute` | `public function withAttribute(string $key, mixed $value): static` | Returns clone with attribute set |
| `withoutAttribute` | `public function withoutAttribute(string ...$keys): static` | Returns clone with attributes removed |
| `getAttribute` | `public function getAttribute(string $key): mixed` | Returns value or null |

---

## 5. Response

### `ResponseInterface`

Interface for immutable HTTP response objects.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(): static` | Factory; creates a 200 response with empty body |
| `json` | `public static function json(mixed $data, int $status = 200): static` | Factory; creates a JSON response with `Content-Type: application/json` |
| `redirect` | `public static function redirect(string $uri, int $status = 302): static` | Factory; creates a redirect response with `Location` header |
| `withStatus` | `public function withStatus(int $code, string $message): static` | Returns clone with the given status code and message |
| `withBody` | `public function withBody(string\|callable $content): static` | Returns clone with the given body (string or callable for streaming) |
| `withHeader` | `public function withHeader(string $name, string $value): static` | Returns clone with header replaced |
| `withAddedHeader` | `public function withAddedHeader(string $name, string $value): static` | Returns clone with value appended to existing header |
| `withHeaders` | `public function withHeaders(array $headers): static` | Returns clone with multiple headers set (values may be string or string[]) |
| `withoutHeaders` | `public function withoutHeaders(string ...$names): static` | Returns clone without the named headers |
| `withAttribute` | `public function withAttribute(string $key, mixed $value): static` | Returns clone with attribute set |
| `withoutAttribute` | `public function withoutAttribute(string ...$keys): static` | Returns clone without the named attributes |
| `getAttribute` | `public function getAttribute(string $key): mixed` | Returns attribute value or null |
| `getStatusCode` | `public function getStatusCode(): int` | Returns the HTTP status code |
| `getStatusMessage` | `public function getStatusMessage(): string` | Returns the status message (auto-resolved from code if not set) |
| `getBody` | `public function getBody(): string\|\Closure` | Returns the response body |
| `getHeader` | `public function getHeader(string $name): mixed` | Returns first value for the named header, or null |
| `getHeaderArray` | `public function getHeaderArray(string $name): array` | Returns all values for the named header |
| `getHeaders` | `public function getHeaders(): array` | Returns all headers as `[$name => $value[]]` |
| `send` | `public function send(): void` | Emits headers and body to the client |

### `Response`

Default implementation. Implements `ResponseInterface`. Uses trait **`HasAttributes`**.

`Response::withStatus` has an optional `$message` parameter (defaults to null; auto-resolved from the built-in `HTTP_STATUS_CODES` constant map).

The `send` method: if `$body` is a `\Closure`, it is invoked (for streaming); otherwise the string is echoed.

---

## 6. Middleware

All middleware classes are invocable (`__invoke`) and follow the signature:

```php
public function __invoke(RequestInterface $request, callable $next): ResponseInterface
```

### `HasMiddleware`

Trait used by **`App`**, **`Router`**, and **`Route`**. Provides the middleware pipeline.

| Method | Signature | Description |
|--------|-----------|-------------|
| `withMiddleware` | `public function withMiddleware(...$middleware): static` | Returns clone with middleware appended |
| `getMiddleware` | `public function getMiddleware(): array` | Returns the array of middleware |
| `handle` | `public function handle(RequestInterface $request): ResponseInterface` | Executes the middleware stack, then calls `handleRequest` |

Requires the using class to implement:

```php
abstract private function handleRequest(RequestInterface $request): ResponseInterface;
```

### `HasNormalizeUriPath`

Trait used by **`Router`**, **`Route`**, and **`Request`**. Provides URI path normalization.

| Method | Signature | Description |
|--------|-----------|-------------|
| `normalizeUriPath` | `private function normalizeUriPath(string $uriPath): string` | Ensures leading slash, removes trailing slash, returns `/` for empty |

### `ErrorMiddleware`

Catches exceptions thrown by downstream middleware/handlers.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(): static` | Factory |
| `withErrorHandler` | `public function withErrorHandler(callable $handler): static` | Returns clone with custom error handler `fn(\Throwable $e, RequestInterface $req): ResponseInterface` |
| `__invoke` | `public function __invoke(RequestInterface $request, callable $next): ResponseInterface` | Wraps `$next` in try/catch; returns 500 on unhandled error |

### `LoggingMiddleware`

Logs method, URI, status code, and elapsed time for each request.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(LoggerInterface $logger): static` | Factory; requires a logger |
| `__invoke` | `public function __invoke(RequestInterface $request, callable $next): ResponseInterface` | Logs `[date] METHOD /uri STATUS Nms` after response |

### `SessionMiddleware`

Starts a PHP session, attaches read-only session/flash data to the request, and writes back from response attributes.

**Constants:**

| Constant | Value | Description |
|----------|-------|-------------|
| `ATTR_SESSION` | `'_pirate_session'` | Request attribute key for the session `PhpSession` |
| `ATTR_FLASH` | `'_pirate_flash'` | Request attribute key for flash data `PhpSession` |
| `ATTR_SESSION_WRITES` | `'_pirate_session_writes'` | Response attribute key; array of session values to persist |
| `ATTR_FLASH_WRITES` | `'_pirate_flash_writes'` | Response attribute key; array of flash values for next request |

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(): static` | Factory |
| `withCookieParams` | `public function withCookieParams(array $params): static` | Returns clone with merged cookie params (default: `httponly=true, samesite=Lax, secure=false`) |
| `__invoke` | `public function __invoke(RequestInterface $request, callable $next): ResponseInterface` | Starts session, attaches data, calls next, writes back, closes session |

### `StaticFileMiddleware`

Serves static files from a directory. Falls through to `$next` if no file matches.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(string $baseDir, array $mimeTypes = []): static` | Factory; `$baseDir` is the document root, `$mimeTypes` merges with built-in map |
| `withBaseDir` | `public function withBaseDir(string $baseDir): static` | Returns clone with new base directory |
| `withMimeTypes` | `public function withMimeTypes(array $mimeTypes): static` | Returns clone with additional MIME type mappings |
| `__invoke` | `public function __invoke(RequestInterface $request, callable $next): ResponseInterface` | Serves file if found; blocks dotfiles; validates path is within base dir |

Built-in MIME types: `html`, `htm`, `css`, `js`, `json`, `xml`, `txt`, `csv`, `png`, `jpg`, `jpeg`, `gif`, `svg`, `ico`, `webp`, `pdf`, `woff`, `woff2`, `ttf`, `eot`, `mp3`, `mp4`, `webm`, `zip`.

### `RateLimitMiddleware`

File-based per-IP rate limiting with a fixed time window.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(int $maxRequests, int $windowSeconds, string $storagePath): static` | Factory; `$storagePath` is a writable directory for counter files |
| `withMaxRequests` | `public function withMaxRequests(int $maxRequests): static` | Returns clone with new limit |
| `withWindowSeconds` | `public function withWindowSeconds(int $windowSeconds): static` | Returns clone with new window duration |
| `withStoragePath` | `public function withStoragePath(string $storagePath): static` | Returns clone with new storage directory |
| `__invoke` | `public function __invoke(RequestInterface $request, callable $next): ResponseInterface` | Returns 429 if limit exceeded; degrades gracefully on storage failure |

### `ContentNegotiationMiddleware`

Renders response data as JSON or HTML based on the `Accept` header.

**Constants:**

| Constant | Value | Description |
|----------|-------|-------------|
| `ATTR_DATA` | `'_pirate_data'` | Response attribute key; set this to trigger content negotiation |

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(RendererInterface $renderer, string $defaultTemplate): static` | Factory |
| `withRenderer` | `public function withRenderer(RendererInterface $renderer): static` | Returns clone with new renderer |
| `withDefaultTemplate` | `public function withDefaultTemplate(string $defaultTemplate): static` | Returns clone with new default template |
| `__invoke` | `public function __invoke(RequestInterface $request, callable $next): ResponseInterface` | If `ATTR_DATA` is set on the response: returns JSON for `Accept: application/json`, otherwise renders the template |

---

## 7. Session

### `SessionInterface`

Read-only session data interface.

| Method | Signature | Description |
|--------|-----------|-------------|
| `get` | `public function get(string $key, mixed $default = null): mixed` | Returns session value, or `$default` |
| `has` | `public function has(string $key): bool` | Returns whether the key exists |
| `all` | `public function all(): array` | Returns all session data |

### `PhpSession`

Default implementation. Implements `SessionInterface`.

| Method | Signature | Description |
|--------|-----------|-------------|
| `__construct` | `public function __construct(array $data = [])` | Creates a session from the given data array |
| `get` | `public function get(string $key, mixed $default = null): mixed` | Returns value or default |
| `has` | `public function has(string $key): bool` | Checks key existence |
| `all` | `public function all(): array` | Returns all data |

To write session data, set the `SessionMiddleware::ATTR_SESSION_WRITES` attribute on the Response. To write flash data, set `SessionMiddleware::ATTR_FLASH_WRITES`.

---

## 8. Cookies

### `CookieInterface`

Read-only cookie access interface.

| Method | Signature | Description |
|--------|-----------|-------------|
| `get` | `public function get(string $name, ?string $default = null): ?string` | Returns cookie value, or `$default` |
| `has` | `public function has(string $name): bool` | Returns whether the cookie exists |
| `all` | `public function all(): array` | Returns all cookies |

### `CookieJar`

Default implementation. Implements `CookieInterface`.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(): static` | Factory; creates an empty jar |
| `fromHeaderString` | `public static function fromHeaderString(string $cookieHeader): static` | Factory; parses a `Cookie` header string into name-value pairs |
| `get` | `public function get(string $name, ?string $default = null): ?string` | Returns cookie value, or `$default` |
| `has` | `public function has(string $name): bool` | Checks cookie existence |
| `all` | `public function all(): array` | Returns all cookies |

---

## 9. Templates

### `RendererInterface`

Template rendering interface.

| Method | Signature | Description |
|--------|-----------|-------------|
| `render` | `public function render(string $template, array $data = []): string` | Renders a template with the given data and returns HTML string |

### `PhpRenderer`

PHP file-based template renderer. Implements `RendererInterface`.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(string $basePath): static` | Factory; `$basePath` is the templates directory |
| `withBasePath` | `public function withBasePath(string $basePath): static` | Returns clone with new base path |
| `getBasePath` | `public function getBasePath(): string` | Returns the base path |
| `render` | `public function render(string $template, array $data = []): string` | Includes the PHP template file with `$data` extracted as variables. Throws `TemplateNotFoundException` if file not found. Path traversal is blocked. |

### `TemplateNotFoundException`

Extends `\RuntimeException`. Thrown when a template file cannot be found.

| Method | Signature | Description |
|--------|-----------|-------------|
| `__construct` | `public function __construct(string $template)` | Sets message to `"Template not found: $template"` |

---

## 10. Logging

### `LoggerInterface`

Minimal logging interface.

| Method | Signature | Description |
|--------|-----------|-------------|
| `log` | `public function log(string $message): void` | Writes a log message |

### `FileLogger`

Appends log lines to a file. Implements `LoggerInterface`.

| Method | Signature | Description |
|--------|-----------|-------------|
| `create` | `public static function create(string $filePath): static` | Factory; `$filePath` is the log file path |
| `withFilePath` | `public function withFilePath(string $filePath): static` | Returns clone with new file path |
| `log` | `public function log(string $message): void` | Appends message as a single line (newlines stripped). Silently swallows write failures. |

---

## Trait Usage Summary

| Trait | Used by |
|-------|---------|
| `HasMiddleware` | `App`, `Router`, `Route` |
| `HasAttributes` | `Request`, `Response` |
| `HasNormalizeUriPath` | `Router`, `Route`, `Request` |
