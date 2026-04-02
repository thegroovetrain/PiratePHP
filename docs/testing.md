# Testing

PiratePHP is designed for testability. The `Request::createFromArrays()` factory lets you create request objects without relying on PHP superglobals, and the `handle()` method available on Route, Router, and App lets you run requests through any layer of the stack and inspect the response.

---

## Creating Test Requests

`Request::createFromArrays()` builds a request from plain arrays:

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
    string $body = ''
): static
```

The `server` array should include at least `REQUEST_URI` and `REQUEST_METHOD` for routing to work. When `headers` is provided, those headers are used directly (normalized to lowercase keys). When `headers` is empty, headers are extracted from the `server` array.

---

## Testing a Single Handler

The simplest test calls a handler function directly:

```php
use thegroovetrain\PiratePHP\Request;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\ResponseInterface;

$handler = function (RequestInterface $request): ResponseInterface {
    $name = $request->getQueryParam('name', 'World');
    return Response::create()->withBody("Hello, {$name}!");
};

$request = Request::createFromArrays(
    query: ['name' => 'Pirate'],
    server: ['REQUEST_URI' => '/hello', 'REQUEST_METHOD' => 'GET']
);

$response = $handler($request);

assert($response->getStatusCode() === 200);
assert($response->getBody() === 'Hello, Pirate!');
```

---

## Testing a Route

Use `$route->handle()` to test a route with its middleware:

```php
use thegroovetrain\PiratePHP\Route;

$route = Route::create()
    ->withPath('/hello')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $name = $request->getQueryParam('name', 'World');
        return Response::create()->withBody("Hello, {$name}!");
    });

$request = Request::createFromArrays(
    query: ['name' => 'Pirate'],
    server: ['REQUEST_URI' => '/hello', 'REQUEST_METHOD' => 'GET']
);

$response = $route->handle($request);

assert($response->getStatusCode() === 200);
assert($response->getBody() === 'Hello, Pirate!');
```

---

## Testing with a Router

The router tests path matching, parameter extraction, and method-based routing:

```php
use thegroovetrain\PiratePHP\Router;

$router = Router::create()
    ->withRoute($route);

$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/hello', 'REQUEST_METHOD' => 'GET']
);

$response = $router->handle($request);
assert($response->getStatusCode() === 200);
```

---

## Testing the Full App Pipeline

Test everything together -- middleware, routers, and routes:

```php
use thegroovetrain\PiratePHP\App;
use thegroovetrain\PiratePHP\ErrorMiddleware;

$app = App::create()
    ->withRouter($router)
    ->withMiddleware(ErrorMiddleware::create());

$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/hello', 'REQUEST_METHOD' => 'GET']
);

$response = $app->handle($request);
assert($response->getStatusCode() === 200);
```

Note: use `$app->handle($request)` for testing rather than `$app->run()`. The `handle()` method accepts a request and returns a response without reading from superglobals or sending output.

---

## Testing POST Requests and JSON

### Form POST

```php
$request = Request::createFromArrays(
    post: ['name' => 'Blackbeard', 'email' => 'bb@pirate.ship'],
    server: ['REQUEST_URI' => '/form', 'REQUEST_METHOD' => 'POST']
);
```

### JSON POST

```php
$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/api/users', 'REQUEST_METHOD' => 'POST'],
    headers: ['Content-Type' => 'application/json'],
    body: json_encode(['name' => 'Blackbeard'])
);

$data = $request->getParsedBody();
assert($data['name'] === 'Blackbeard');
```

For details on `getParsedBody()` and other request methods, see [Request](request.md).

---

## Testing 404 and 405

```php
// No route matches -> 404
$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/nonexistent', 'REQUEST_METHOD' => 'GET']
);
$response = $router->handle($request);
assert($response->getStatusCode() === 404);

// Path matches but method doesn't -> 405
$request = Request::createFromArrays(
    server: ['REQUEST_URI' => '/hello', 'REQUEST_METHOD' => 'DELETE']
);
$response = $router->handle($request);
assert($response->getStatusCode() === 405);
```

---

## PHPUnit Example

Here is a complete PHPUnit test class:

```php
use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\Request;
use thegroovetrain\PiratePHP\Route;
use thegroovetrain\PiratePHP\Router;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\ResponseInterface;

class AppTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $route = Route::create()
            ->withPath('/greet/:name')
            ->withMethods('GET')
            ->withHandler(function (RequestInterface $request): ResponseInterface {
                $name = $request->getAttribute('name');
                return Response::json(['greeting' => "Ahoy, {$name}!"]);
            });

        $this->router = Router::create()->withRoute($route);
    }

    public function testGreetReturnsJson(): void
    {
        $request = Request::createFromArrays(
            server: ['REQUEST_URI' => '/greet/Blackbeard', 'REQUEST_METHOD' => 'GET']
        );

        $response = $this->router->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        $body = json_decode($response->getBody(), true);
        $this->assertEquals('Ahoy, Blackbeard!', $body['greeting']);
    }

    public function testWrongMethodReturns405(): void
    {
        $request = Request::createFromArrays(
            server: ['REQUEST_URI' => '/greet/Blackbeard', 'REQUEST_METHOD' => 'POST']
        );

        $response = $this->router->handle($request);
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testUnknownPathReturns404(): void
    {
        $request = Request::createFromArrays(
            server: ['REQUEST_URI' => '/unknown', 'REQUEST_METHOD' => 'GET']
        );

        $response = $this->router->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
```

---

## Related Pages

- [Request](request.md) -- The full Request API including `createFromArrays()`
- [Response](response.md) -- Inspecting response status codes, headers, and bodies
- [Routing](routing.md) -- How routes and routers match requests
- [Getting Started](getting-started.md) -- The handler contract
