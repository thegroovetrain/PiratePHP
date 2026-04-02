# ContentNegotiationMiddleware

ContentNegotiationMiddleware inspects the `Accept` header and transforms structured data into the appropriate response format. Handlers return data by attaching it to the response as an attribute (via `ContentNegotiationMiddleware::ATTR_DATA`). The middleware then formats it as JSON or renders it through an HTML template, depending on what the client accepts.

## Configuration

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

## Usage

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

In your handler, attach data to the response using `ContentNegotiationMiddleware::ATTR_DATA` instead of formatting it yourself:

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

**Setting a status code:** The original status code is preserved through the transformation. If your handler returns a 201 response with `ATTR_DATA`, the final JSON or HTML response will also be 201.

```php
return Response::create()
    ->withStatus(201)
    ->withAttribute(ContentNegotiationMiddleware::ATTR_DATA, ['id' => 42, 'created' => true]);
```

## What It Reads / What It Writes

**Reads from the request:**

- `$request->getHeader('accept')` -- the Accept header, checked for `'application/json'`

**Reads from the response:**

- `ContentNegotiationMiddleware::ATTR_DATA` -- the data to transform

**Writes to the response (if `ATTR_DATA` is present):**

- **JSON path:** Replaces the body with `json_encode($data)`, sets `Content-Type: application/json`. Preserves the original status code and all non-Content-Type headers.
- **HTML path:** Calls `$renderer->render($defaultTemplate, $data)`, replaces the body with the rendered HTML, sets `Content-Type: text/html`. If `$data` is not an array, it is wrapped as `['data' => $data]`. Preserves the original status code and all non-Content-Type headers.

**If `ATTR_DATA` is not present:** the response passes through unchanged.

## Edge Cases and Gotchas

- **No ATTR_DATA, no transformation.** If your handler does not attach `ContentNegotiationMiddleware::ATTR_DATA` to the response, the middleware is a no-op. This means you can mix content-negotiated routes and manually-formatted routes under the same middleware.
- **JSON encoding errors** return a `500` response with the body `'JSON encoding error'`. Original headers are lost.
- **Template rendering errors** return a `500` response with the body `'Template rendering error'`. Original headers are lost.
- **Accept header checking is simple.** It checks `str_contains($accept, 'application/json')`. It does not parse quality values or handle `*/*`. If the Accept header contains `application/json` anywhere, you get JSON. Otherwise, you get HTML.
- **Non-array data for templates.** If `$data` is not an array (e.g., a string or an object), it is wrapped in `['data' => $data]` before being passed to the renderer. For JSON, any JSON-encodable value works as-is.
- **Original headers are preserved.** Headers from the original response (set by the handler) are copied to the new response, except for `Content-Type` which is overwritten by the middleware.
- **Typically route- or router-level.** This middleware makes most sense on API routers or specific routes, not as app-level middleware, since not every route returns structured data.

## Related

- [Middleware Overview](README.md) -- how the onion pipeline works, middleware ordering, and custom middleware examples
