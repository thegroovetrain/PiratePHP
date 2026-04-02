# PiratePHP Architecture

## 1. Design Philosophy

PiratePHP is built on a single idea: **immutable functional composition**. Every object
in the framework follows the same pattern -- `with*()` methods clone the object, modify
the clone, and return it. The original is never touched. There is no mutable state in
user-facing code. There is no global state that leaks between requests or components.

The framework has **zero production dependencies**. The `composer.json` declares only
`require-dev` entries (PHPUnit, Mockery). The entire runtime is 28 source files in
`src/`, each under 130 lines. Nothing external is needed to run a PiratePHP application.

PiratePHP is **not PSR-7 or PSR-15 compliant**, and this is deliberate. PSR compliance
would mean importing interfaces from external packages (violating zero-dependency), and
would force the framework to accommodate patterns it does not use -- writable message
bodies via StreamInterface, server-request factories, handler/middleware interfaces with
different signatures than the simple `callable($request, $next)` pattern PiratePHP uses.

The tradeoff is coherence over interoperability. Every component in PiratePHP works the
same way. Middleware is a callable. Handlers are callables. Configuration is done through
`with*()` methods. There is one way to do things, and that one way is consistent from
`App` down to `Route`.

---

## 2. The with*/clone Pattern

Every mutable operation in PiratePHP follows the clone-and-return pattern. Here is how
it works at the PHP level, taken directly from `HasMiddleware::withMiddleware()` (line 9
of `src/HasMiddleware.php`):

```php
public function withMiddleware(...$middleware):static
{
    $new = clone $this;
    $new->middleware = [...$this->middleware, ...$middleware];
    return $new;
}
```

Three steps, always:

1. `clone $this` -- PHP creates a shallow copy of the object. All scalar and array
   properties are duplicated. The original object is untouched.
2. Modify the clone's property -- the clone gets the new value. The spread operator
   `[...$this->middleware, ...$middleware]` builds a new array that includes both the
   old and new middleware. The original's `$middleware` array is never modified.
3. `return $new` -- the caller receives the modified clone. The original stays frozen.

This pattern appears in every component:

| Class                          | with* methods                                                         |
|-------------------------------|-----------------------------------------------------------------------|
| `App`                          | `withRouter()`, `withMiddleware()`                                   |
| `Router`                       | `withBasePath()`, `withRoute()`, `withMiddleware()`                  |
| `Route`                        | `withPath()`, `withHandler()`, `withMethods()`, `withMiddleware()`, `withName()` |
| `Request`                      | `withAttribute()`, `withoutAttribute()`                              |
| `Response`                     | `withStatus()`, `withBody()`, `withHeader()`, `withAddedHeader()`, `withHeaders()`, `withoutHeaders()`, `withAttribute()`, `withoutAttribute()`, `withSession()`, `withFlash()` |
| `PhpSession`                   | `with()`, `without()`                                                |
| `ErrorMiddleware`              | `withErrorHandler()`                                                 |
| `RateLimitMiddleware`          | `withMaxRequests()`, `withWindowSeconds()`, `withStoragePath()`      |
| `ContentNegotiationMiddleware` | `withRenderer()`, `withDefaultTemplate()`                            |
| `FileLogger`                   | `withFilePath()`                                                     |
| `PhpRenderer`                  | `withBasePath()`                                                     |

This enables composition without side effects. You can create a "template" route and
derive variations from it -- the original template is never altered:

```php
$apiRoute = Route::create()
    ->withPath('/api')
    ->withMiddleware($authMiddleware);

$usersRoute = $apiRoute->withPath('/users')->withMethods('GET')
    ->withHandler($listUsersHandler);

$postsRoute = $apiRoute->withPath('/posts')->withMethods('GET')
    ->withHandler($listPostsHandler);

// $apiRoute still has path '/api', no handler, no methods.
// $usersRoute has path '/api/users', method GET, auth middleware, and a handler.
// $postsRoute has path '/api/posts', method GET, auth middleware, and a handler.
```

---

## 3. Request Lifecycle

```
  HTTP Request (from web server)
         |
         v
+------------------+
|   App::run()     |  (src/App.php:26)
|                  |  Creates Request from superglobals via Request::create()
+--------+---------+
         |
         v
+------------------+
|  App::handle()   |  (from HasMiddleware trait)
|                  |  Enters App-level middleware pipeline
+--------+---------+
         |
         v
+----------------------------+
|  App-level Middleware      |  e.g. ErrorMiddleware, LoggingMiddleware,
|  (processed recursively)  |       RateLimitMiddleware
|                            |
|  Each calls $next($req)   |
|  to pass control forward  |
+------------+---------------+
             |
             v
+------------------------+
|  App::handleRequest()  |  (src/App.php:34)
|                        |  Sorts routers by basePath length (longest first).
|                        |  Matches request URI prefix to router basePath.
+----------+-------------+
           |
           v
+------------------------+
|  Router::handle()      |  (from HasMiddleware trait)
|                        |  Enters Router-level middleware pipeline
+----------+-------------+
           |
           v
+----------------------------+
|  Router-level Middleware   |
|  (processed recursively)  |
+------------+---------------+
             |
             v
+-----------------------------+
|  Router::handleRequest()    |  (src/Router.php:78)
|                             |  Iterates routes. Prepends basePath to each
|                             |  route path. Converts :param segments to regex
|                             |  capture groups. Tests path match, then method.
|                             |  Extracts route params into request attributes.
+-----------+-----------------+
            |
            v
+------------------------+
|  Route::handle()       |  (from HasMiddleware trait)
|                        |  Enters Route-level middleware pipeline
+----------+-------------+
           |
           v
+----------------------------+
|  Route-level Middleware    |
|  (processed recursively)  |
+------------+---------------+
             |
             v
+---------------------------+
|  Route::handleRequest()   |  (src/Route.php:120)
|                           |  Calls the user's handler:
|                           |  call_user_func($this->handler, $request)
|                           |  Handler returns a Response.
+-----------+---------------+
            |
            v
     Response bubbles back
     through middleware at
     each level (Route ->
     Router -> App), each
     middleware can inspect
     or transform the
     Response before
     returning it.
            |
            v
+------------------+
| Response::send() |  (src/Response.php:232)
|                  |  Emits headers via header(), sets http_response_code(),
|                  |  outputs body (echo or invokes Closure for streaming).
+------------------+
```

The key insight: `Request` flows **forward** through middleware (each middleware receives
it and can modify it via `withAttribute()` before calling `$next`), while `Response`
flows **backward** (each middleware receives the response returned by `$next()` and can
modify it before returning it to the layer above).

---

## 4. Route Composition

### Why withPath() concatenates

`Route::withPath()` (src/Route.php:39) does not replace the path -- it **appends** to it.
The existing path and the new segment are concatenated:

```php
$existingPath = $this->path ?? '';
$newSegments = implode('/', $validatedSegments);
$combined = rtrim($existingPath, '/') . '/' . ltrim($newSegments, '/');
$new->path = $this->normalizeUriPath($combined);
```

This is the foundation of the "template route" pattern. A base route defines shared
configuration (path prefix, middleware), and derived routes extend it:

```php
$admin = Route::create()
    ->withPath('/admin')
    ->withMiddleware($authMiddleware);

$dashboard = $admin->withPath('/dashboard')   // path: /admin/dashboard
    ->withMethods('GET')
    ->withHandler($dashboardHandler);

$settings = $admin->withPath('/settings')     // path: /admin/settings
    ->withMethods('GET', 'POST')
    ->withHandler($settingsHandler);
```

Both `$dashboard` and `$settings` inherit the `/admin` prefix and the `$authMiddleware`
from `$admin`. The middleware array is cloned along with everything else, so the derived
routes carry the middleware without any explicit grouping mechanism.

### Why RouteGroup was removed

Other frameworks (Slim, Laravel) provide a `RouteGroup` or `Route::group()` concept for
applying shared prefixes and middleware. In PiratePHP, this is unnecessary because:

1. **withPath() concatenation** gives you prefix inheritance for free.
2. **Clone-based composition** gives you middleware inheritance for free.
3. A route group would be a separate concept with its own API, adding complexity without
   adding capability.

The template route pattern achieves the same result with fewer concepts and zero special
syntax.

### Comparison to other frameworks

| Framework   | Grouping mechanism      | PiratePHP equivalent              |
|-------------|------------------------|-----------------------------------|
| Slim 4      | `$app->group('/api', ...)` | Template route with `->withPath('/api')` |
| Laravel     | `Route::prefix('/api')->group(...)` | Same as above                |
| Express.js  | `express.Router()` mounted at prefix | `Router::create()->withBasePath('/api')` |

---

## 5. Component Architecture

```
+------------------------------------------------------------------+
|                        INTERFACES                                 |
|                                                                   |
|  AppInterface    RequestInterface    ResponseInterface            |
|  RouterInterface RouteInterface      SessionInterface             |
|  CookieInterface RendererInterface   LoggerInterface              |
+------------------------------------------------------------------+

+------------------------------------------------------------------+
|                         TRAITS                                    |
|                                                                   |
|  HasMiddleware          HasAttributes         HasNormalizeUriPath |
|  - $middleware          - $attributes         - normalizeUriPath()|
|  - withMiddleware()     - withAttribute()                         |
|  - getMiddleware()      - withoutAttribute()                      |
|  - handle()             - getAttribute()                          |
|  - processMiddleware()                                            |
|  - handleRequest() [abstract]                                     |
+------------------------------------------------------------------+

+------------------------------------------------------------------+
|                      CORE CLASSES                                 |
|                                                                   |
|  App ----uses----> HasMiddleware                                  |
|   |                                                               |
|   |  contains 0..n                                                |
|   v                                                               |
|  Router --uses---> HasMiddleware, HasNormalizeUriPath             |
|   |                                                               |
|   |  contains 0..n                                                |
|   v                                                               |
|  Route ---uses---> HasMiddleware, HasNormalizeUriPath             |
|   |                                                               |
|   |  calls                                                        |
|   v                                                               |
|  Handler (user callable) -> returns Response                      |
|                                                                   |
|  Request --uses--> HasAttributes, HasNormalizeUriPath             |
|  Response -uses--> HasAttributes                                  |
+------------------------------------------------------------------+

+------------------------------------------------------------------+
|                     MIDDLEWARE CLASSES                             |
|  (all are invokable: __invoke($request, $next))                  |
|                                                                   |
|  ErrorMiddleware              LoggingMiddleware                   |
|  RateLimitMiddleware          ContentNegotiationMiddleware        |
|  ContentNegotiationMiddleware                                     |
+------------------------------------------------------------------+

+------------------------------------------------------------------+
|                    SUPPORT CLASSES                                 |
|                                                                   |
|  PhpSession       implements SessionInterface                    |
|  CookieJar        implements CookieInterface                     |
|  PhpRenderer      implements RendererInterface                   |
|  FileLogger       implements LoggerInterface                     |
|  TemplateNotFoundException   extends RuntimeException             |
+------------------------------------------------------------------+
```

### Trait usage map

| Trait                 | Used by                    |
|-----------------------|----------------------------|
| `HasMiddleware`       | `App`, `Router`, `Route`   |
| `HasAttributes`       | `Request`, `Response`      |
| `HasNormalizeUriPath` | `Request`, `Router`, `Route` |

### Constructor pattern

Every class uses a **private constructor** with a **static factory** (`create()` or a
purpose-specific factory like `fromHeaderString()`, `createFromArrays()`). This enforces
that objects are always created through a controlled entry point and prevents subclasses
from bypassing initialization logic.

---

## 6. Middleware Pipeline

### How HasMiddleware::processMiddleware() works

The middleware pipeline is implemented as a recursive function in `src/HasMiddleware.php`
(line 29):

```php
private function processMiddleware(RequestInterface $request, int $index):ResponseInterface
{
    if(isset($this->middleware[$index])) {
        $middleware = $this->middleware[$index];
        $next = function (RequestInterface $request) use ($index):ResponseInterface {
            return $this->processMiddleware($request, $index + 1);
        };
        return $middleware($request, $next);
    } else {
        return $this->handleRequest($request);
    }
}
```

The recursion works like this:

1. Start at index 0.
2. If middleware exists at that index, call it with the request and a `$next` closure.
3. The `$next` closure, when invoked by the middleware, calls `processMiddleware` with
   index + 1.
4. When the index exceeds the middleware array, call the abstract `handleRequest()`
   method -- this is the "terminal handler" that each class implements differently.

This creates a call stack that naturally wraps around the handler:

```
processMiddleware(req, 0)
  middleware[0](req, next)    <-- ErrorMiddleware wraps everything
    processMiddleware(req, 1)
      middleware[1](req, next)    <-- LoggingMiddleware times the request
        processMiddleware(req, 2)
          handleRequest(req)      <-- terminal: no more middleware
        <-- returns Response
      <-- LoggingMiddleware logs, returns Response
    <-- returns Response
  <-- ErrorMiddleware catches exceptions, returns Response
```

### Three levels of middleware

Middleware can be attached at three levels. Each level has its own `HasMiddleware` trait
instance, so each level runs its own pipeline independently:

```
+--------------------------------------------------+
|  APP MIDDLEWARE                                   |
|  App::withMiddleware(ErrorMiddleware, ...)        |
|                                                   |
|  Runs first. Wraps everything. Good for:         |
|  - Error handling                                 |
|  - Logging                                        |
|  - Rate limiting                                  |
|                                                   |
|  +----------------------------------------------+|
|  |  ROUTER MIDDLEWARE                            ||
|  |  Router::withMiddleware(...)                  ||
|  |                                               ||
|  |  Runs for all routes in this router.          ||
|  |  Good for: router-scoped auth, CORS           ||
|  |                                               ||
|  |  +------------------------------------------+||
|  |  |  ROUTE MIDDLEWARE                         |||
|  |  |  Route::withMiddleware(...)               |||
|  |  |                                           |||
|  |  |  Runs for this single route only.         |||
|  |  |  Good for: route-specific validation      |||
|  |  |                                           |||
|  |  |  +--------------------------------------+ |||
|  |  |  |  HANDLER                             | |||
|  |  |  |  Route::handleRequest() calls the    | |||
|  |  |  |  user's handler function             | |||
|  |  |  +--------------------------------------+ |||
|  |  +------------------------------------------+||
|  +----------------------------------------------+|
+--------------------------------------------------+
```

These three levels compose into a single nested pipeline at runtime. The App middleware
runs, eventually calling `App::handleRequest()` which finds the right Router. The Router's
`handle()` enters the Router middleware, which eventually calls `Router::handleRequest()`
to find the right Route. The Route's `handle()` enters the Route middleware, which
eventually calls `Route::handleRequest()` to invoke the user's handler.

The middleware signature is always `function(RequestInterface $request, callable $next): ResponseInterface`. No interfaces to implement, no abstract classes to extend. A middleware
is just a callable.

---

## 7. Session Design

### The immutable session problem

PHP sessions are inherently global mutable state: `session_start()` opens a file,
`$_SESSION` is a superglobal array, and `session_write_close()` flushes it. This
directly conflicts with PiratePHP's immutability model.

### How Request and Response bridge the gap

Sessions are first-class citizens of the Request/Response lifecycle. There is no
session middleware. Instead, `Request::create()` starts the session and reads data
into immutable objects, and `Response::send()` writes changes back.

```
  Request::create()
       |
       v
  1. session_start() with secure defaults
     Read $_SESSION into PhpSession
     Read $_SESSION['_flash'] into flash PhpSession
     Clear flash from $_SESSION
       |
       v
  2. Request carries session + flash as properties:
       $request->getSession()  => PhpSession
       $request->getFlash()    => PhpSession
       |
       v
  3. Request flows through middleware and handlers.
     Handler reads session via $request->getSession().
     Handler builds updated session with ->with() / ->without().
     Handler attaches updated session to Response:
       $response->withSession($updatedSession)
       $response->withFlash([...])
       |
       v
  4. Response::send()
     Write session data to $_SESSION
     Write flash to $_SESSION['_flash']
     session_write_close()
     Emit headers and body.
```

### PhpSession is immutable

`PhpSession` (src/PhpSession.php) has `get()`, `has()`, `all()` for reading, and
`with()` and `without()` for building new instances. The `with()` and `without()`
methods clone the object and return the modified copy, following PiratePHP's standard
immutable pattern.

### Writing session data

Handlers write session data by building an updated session from the request's session
and attaching it to the response:

```php
$session = $request->getSession()
    ->with('user', 'alice')
    ->without('guest_token');

return Response::create()
    ->withBody('OK')
    ->withSession($session)
    ->withFlash(['success' => 'Saved!']);
```

`Response::send()` writes the session data to `$_SESSION` and flash data to
`$_SESSION['_flash']`, then calls `session_write_close()`.

### Constraints

- Session reads reflect the state at the **start** of the request. Changes made with
  `with()` produce a new `PhpSession` object; they are not visible in the original
  `$request->getSession()`.
- Flash data is one-shot: read from the prior request, cleared immediately, and new flash
  data is written for the next request.
- Session cookie defaults are secure: `httponly => true`, `samesite => 'Lax'`. The
  `secure` flag is auto-detected from `$_SERVER['HTTPS']`.

---

## 8. Security Model

### Path traversal protection

`PhpRenderer` uses **realpath validation** as its defense against path traversal attacks:

```php
$realBase = realpath($this->basePath);
$realFile = realpath($file);

if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase)) {
    throw new TemplateNotFoundException($template);
}
```

This resolves all `../`, symlinks, and encoding tricks to their actual filesystem paths,
then verifies the resolved file is actually within the intended base directory.

### Session cookie defaults

`Request::create()` sets secure cookie defaults before starting the session
(src/Request.php, lines 36-41):

- `httponly => true` -- JavaScript cannot access the session cookie, preventing XSS-based
  session theft.
- `samesite => 'Lax'` -- the cookie is not sent on cross-origin POST requests, providing
  baseline CSRF protection.
- `secure` -- auto-detected from `$_SERVER['HTTPS']` at runtime. If HTTPS is active, the
  cookie is only sent over encrypted connections.

These defaults are always applied. There is no configuration method to override them.

### Rate limiting

`RateLimitMiddleware` (src/RateLimitMiddleware.php) identifies clients by
`$_SERVER['REMOTE_ADDR']` (line 54). It does **not** use `X-Forwarded-For` or any other
proxy header by default.

This is intentional. `X-Forwarded-For` is trivially spoofable by clients. Trusting it
would allow attackers to bypass rate limits by rotating the header value. If the
application runs behind a trusted reverse proxy, the proxy should set `REMOTE_ADDR`
correctly (most production setups do this via the web server configuration, not the
application).

The rate limiter uses file-based storage with `flock()` for atomic reads/writes
(lines 63-94). If storage fails (directory missing, permissions error), the middleware
degrades gracefully and allows the request through (lines 65-66, lines 102-104).

---

## 9. Testing Architecture

### Request::createFromArrays() as the key to testability

The central testing enabler is `Request::createFromArrays()` (src/Request.php, line 38):

```php
public static function createFromArrays(
    array $query = [],
    array $post = [],
    array $server = [],
    array $headers = [],
    string $body = '',
    array $session = [],
    array $flash = []
):static
```

This factory builds a `Request` from plain arrays instead of PHP superglobals (`$_GET`,
`$_POST`, `$_SERVER`). The `session` and `flash` parameters inject session and flash data
without starting a real PHP session. This means the entire framework -- including session
handling -- can be tested without a web server and without modifying superglobals.

### Testing the full App pipeline

The test suite demonstrates full-stack testing without superglobals. From
`tests/unit/RequestFactoryTest.php`:

```php
$request = Request::createFromArrays(
    ['q' => 'search'],                          // $_GET equivalent
    ['name' => 'test'],                          // $_POST equivalent
    ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/users']  // $_SERVER equivalent
);
```

For handler and middleware tests, the pattern is: create a Request from arrays, call
`handle()` or invoke the middleware directly, and assert on the returned Response:

```php
$request = Request::createFromArrays([], [], [
    'REQUEST_METHOD' => 'GET',
    'REQUEST_URI' => '/',
]);
$response = $middleware($request, function ($req) {
    return Response::create()->withStatus(200);
});
$this->assertSame(200, $response->getStatusCode());
```

### Test file organization

All tests live in `tests/unit/` and follow the pattern `{ClassName}Test.php`:

| Test file                         | What it covers                              |
|----------------------------------|---------------------------------------------|
| `AppTest.php`                     | App creation, router wiring, full run cycle |
| `AppPrefixTest.php`               | Router basepath matching edge cases         |
| `RouterTest.php`                  | Route matching, method filtering, params    |
| `RouteTest.php`                   | Route creation, path building, validation   |
| `RouteParamsTest.php`             | Parameter extraction from URL segments      |
| `RequestTest.php`                 | Request getters, attributes                 |
| `RequestFactoryTest.php`          | createFromArrays, header parsing, body      |
| `ResponseTest.php`                | Status, body, header basics                 |
| `ResponseHelpersTest.php`         | json(), redirect() factory methods          |
| `ResponseHeaderArrayTest.php`     | Multi-value header support                  |
| `ResponseAttributeTest.php`       | Response attribute get/set                  |
| `MiddlewareTest.php`              | HasMiddleware trait pipeline behavior        |
| `RequestSessionTest.php`          | Request session/flash reads                 |
| `ResponseSessionTest.php`        | Response session/flash writes                |
| `ErrorMiddlewareTest.php`         | Error catching, custom handlers             |
| `RateLimitMiddlewareTest.php`     | Rate limiting behavior                      |
| `ContentNegotiationTest.php`      | JSON vs HTML response based on Accept       |
| `LoggingMiddlewareTest.php`       | Request logging                             |
| `PhpRendererTest.php`             | Template rendering, path traversal          |
| `PhpSessionTest.php`              | Immutable session object                    |
| `PhpSessionWriteTest.php`        | PhpSession with() and without() methods     |
| `CookieJarTest.php`              | Cookie parsing and access                   |
| `FileLoggerTest.php`              | File-based logging                          |

Session tests use `@runTestsInSeparateProcesses` and `@preserveGlobalState disabled`
annotations because they necessarily interact with PHP's global session machinery.

---

## 10. Design Decisions Log

### No PSR compliance

**Decision:** Do not implement PSR-7 (HTTP Messages) or PSR-15 (Middleware).

**Why:** PSR-7 requires `psr/http-message` as a dependency, violating zero-dependency.
PSR-7's `StreamInterface` for message bodies adds complexity PiratePHP doesn't need --
bodies are either strings or Closures (for streaming). PSR-15's `MiddlewareInterface`
and `RequestHandlerInterface` impose a class-based middleware pattern; PiratePHP uses
plain callables, which are simpler and more flexible. Adopting PSR interfaces would mean
maintaining compatibility with external contracts instead of optimizing for internal
coherence.

### Zero dependencies

**Decision:** No production `require` entries in `composer.json`.

**Why:** Every dependency is a liability -- it can break, introduce vulnerabilities, or
conflict with other packages. PiratePHP's scope is small enough that external packages
add more coupling than value. The framework provides its own interfaces for logging
(`LoggerInterface`), rendering (`RendererInterface`), sessions (`SessionInterface`), and
cookies (`CookieInterface`).

### withPath() concatenates

**Decision:** `Route::withPath('/users')` on a route with path `/api` produces `/api/users`,
not `/users`.

**Why:** This enables the template route pattern where a base route defines a shared prefix
and derived routes extend it. If `withPath()` replaced the path, you would need a separate
grouping mechanism (like `RouteGroup`) to share prefixes. Concatenation makes grouping
redundant. One pattern handles both cases.

### Static factories over constructors

**Decision:** All classes have `private function __construct()` and `public static function create()`.

**Why:** Static factories provide a consistent creation API across all classes. They allow
purpose-specific factory methods alongside the general one (e.g., `Response::json()`,
`Response::redirect()`, `Request::createFromArrays()`, `CookieJar::fromHeaderString()`).
Private constructors prevent users from bypassing the intended creation path.

### Headers stored as arrays internally

**Decision:** Response headers are stored as `array<string, string[]>` -- each header name
maps to an array of values.

**Why:** HTTP allows multiple values for the same header (e.g., `Set-Cookie`). Storing
values as arrays preserves this capability. `withHeader()` replaces all values for a name
(`$new->headers[$name] = [$value]`), while `withAddedHeader()` appends
(`$new->headers[$name] = [...($this->headers[$name] ?? []), $value]`). The `getHeader()`
convenience method returns the first value; `getHeaderArray()` returns all values.
`Response::send()` (line 235-239) iterates all values and emits each as a separate
`header()` call with `false` as the replace parameter, ensuring all values are sent.

### Callable body for streaming

**Decision:** `Response::withBody()` accepts `string|callable`. When the body is a
`Closure`, `Response::send()` invokes it instead of echoing a string.

**Why:** This avoids loading large responses into memory. For example, a handler can
stream a file directly to the output buffer:

```php
->withBody(function () use ($filePath) {
    readfile($filePath);
})
```

The file is read directly to the output buffer during `send()`, never stored in a PHP
string. The callable is normalized to a `Closure` via `\Closure::fromCallable()` to
ensure consistent typing (src/Response.php, lines 137-138).
