# Routing

PiratePHP's routing system matches incoming HTTP requests to handlers by URL path and HTTP method, with support for dynamic parameters, path composition, named routes, and subrouters.

---

## Basic Routes

Create a route with `Route::create()`, then configure it with `withPath()`, `withMethods()`, and `withHandler()`:

```php
use thegroovetrain\PiratePHP\Route;
use thegroovetrain\PiratePHP\Router;
use thegroovetrain\PiratePHP\RequestInterface;
use thegroovetrain\PiratePHP\Response;
use thegroovetrain\PiratePHP\ResponseInterface;

$home = Route::create()
    ->withPath('/')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('Home page');
    });

$about = Route::create()
    ->withPath('/about')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('About page');
    });

$router = Router::create()
    ->withRoute($home, $about);
```

You can pass multiple routes to `withRoute()` at once or chain multiple calls -- both work.

### Method Matching

A route can accept multiple HTTP methods:

```php
$route = Route::create()
    ->withPath('/data')
    ->withMethods('GET', 'POST')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $method = $request->getMethod();
        return Response::create()->withBody("Method: {$method}");
    });
```

If a path matches but the HTTP method does not, PiratePHP returns a `405 Method Not Allowed` response. If no path matches at all, it returns `404 Not Found`.

---

## Dynamic Parameters

Use `:paramName` segments to capture values from the URL:

```php
$userRoute = Route::create()
    ->withPath('/user/:id')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::create()->withBody("User ID: {$id}");
    });
```

When a request hits `/user/42`, PiratePHP extracts `42` and attaches it as a request attribute. Read it with `$request->getAttribute('id')`. For the full request attribute API, see [Request](request.md).

You can use multiple parameters in one path:

```php
$postRoute = Route::create()
    ->withPath('/user/:userId/post/:postId')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $userId = $request->getAttribute('userId');
        $postId = $request->getAttribute('postId');
        return Response::create()->withBody("User {$userId}, Post {$postId}");
    });
```

Parameter names must start with a letter or underscore and contain only word characters (letters, digits, underscores).

---

## Route Composition (The Template Route Pattern)

This is the PiratePHP way of organizing routes. The key insight: **`withPath()` concatenates**. If a route already has a path, calling `withPath()` again appends to it.

Create a "template route" with a shared prefix, then derive child routes from it:

```php
// Template route: shared prefix, no handler yet
$apiBase = Route::create()->withPath('/api');

// Child routes: withPath() appends to "/api"
$listUsers = $apiBase
    ->withPath('/users')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::json([
            ['id' => 1, 'name' => 'Blackbeard'],
            ['id' => 2, 'name' => 'Anne Bonny'],
        ]);
    });

$getUser = $apiBase
    ->withPath('/users/:id')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::json(['id' => $id, 'name' => 'Pirate ' . $id]);
    });

$router = Router::create()
    ->withRoute($listUsers, $getUser);
```

`$listUsers` has the path `/api/users`. `$getUser` has the path `/api/users/:id`. Both were derived from `$apiBase` which provided the `/api` prefix.

Because everything is immutable, `$apiBase` is never modified. You can derive as many child routes from it as you like.

### Sharing Middleware via Template Routes

Template routes can also carry middleware that all child routes inherit:

```php
$authMiddleware = function (RequestInterface $request, callable $next): ResponseInterface {
    $token = $request->getHeader('authorization');
    if ($token !== 'Bearer secret-token') {
        return Response::json(['error' => 'Unauthorized'], 401);
    }
    return $next($request);
};

$adminBase = Route::create()
    ->withPath('/admin')
    ->withMiddleware($authMiddleware);

$dashboard = $adminBase
    ->withPath('/dashboard')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('Admin Dashboard');
    });

$settings = $adminBase
    ->withPath('/settings')
    ->withMethods('GET')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        return Response::create()->withBody('Admin Settings');
    });
```

Both `/admin/dashboard` and `/admin/settings` run through `$authMiddleware` because they were derived from `$adminBase`. This pattern scales well -- you can nest template routes multiple levels deep.

---

## Named Routes and URL Generation

Give a route a name with `withName()`, then generate its URL with `$router->urlFor()`:

```php
$userRoute = Route::create()
    ->withPath('/user/:id')
    ->withMethods('GET')
    ->withName('user.show')
    ->withHandler(function (RequestInterface $request): ResponseInterface {
        $id = $request->getAttribute('id');
        return Response::create()->withBody("User {$id}");
    });

$router = Router::create()
    ->withRoute($userRoute);

// Generate URLs
$url = $router->urlFor('user.show', ['id' => '42']);
// Result: "/user/42"
```

`urlFor()` replaces `:param` placeholders with the values you provide and URL-encodes them with `rawurlencode()`.

If the router has a base path, it is prepended automatically:

```php
$router = Router::create()
    ->withBasePath('/v1')
    ->withRoute($userRoute);

$url = $router->urlFor('user.show', ['id' => '42']);
// Result: "/v1/user/42"
```

If you reference a route name that does not exist, `urlFor()` throws a `\RuntimeException`.

---

## Subrouters with withBasePath()

Use `withBasePath()` on a `Router` to namespace a group of routes under a URL prefix:

```php
$apiRouter = Router::create()
    ->withBasePath('/api/v1')
    ->withRoute($listUsers, $getUser);

$webRouter = Router::create()
    ->withRoute($home, $about);

$app = App::create()
    ->withRouter($apiRouter, $webRouter);
```

When multiple routers exist, `App` sorts them by base path length (longest first) so more specific paths match before general ones. A request to `/api/v1/users` matches `$apiRouter`, while `/about` matches `$webRouter`.

The base path is prepended to every route path in that router during matching, and is included when generating URLs with `urlFor()`.

---

## Related Pages

- [Getting Started](getting-started.md) -- Installation and hello world
- [Request](request.md) -- Reading route parameters, query strings, and request data
- [Response](response.md) -- Building responses with JSON, redirects, and headers
- [Testing](testing.md) -- Testing routes and routers with synthetic requests
