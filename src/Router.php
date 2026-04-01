<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class Router implements RouterInterface
{
    use HasMiddleware;
    use HasNormalizeUriPath;

    private string $basepath;
    private array $routes = [];
    private array $namedRoutes = [];


    private function __construct(string $basepath="/")
    {
        $this->basepath = $this->normalizeUriPath($basepath);
    }


    public static function create():static
    {
        return new static();
    }


    public function withBasePath(string $basepath):static
    {
        $new = clone $this;
        $new->basepath = $this->normalizeUriPath($basepath);
        return $new;
    }


    public function withRoute(RouteInterface ...$route):static
    {
        $new = clone $this;
        $new->routes = [...$this->routes, ...$route];
        foreach ($route as $r) {
            if ($r->getName() !== null) {
                $new->namedRoutes[$r->getName()] = $r;
            }
        }
        return $new;
    }


    public function getBasePath():string
    {
        return $this->basepath;
    }


    public function getRoutes():array
    {
        return $this->routes;
    }


    public function urlFor(string $name, array $params = []):string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException("Named route '{$name}' not found.");
        }
        $route = $this->namedRoutes[$name];
        $path = $route->getPath();
        foreach ($params as $key => $value) {
            $path = str_replace(':' . $key, $value, $path);
        }
        if ($this->basepath !== '' && $this->basepath !== '/') {
            $path = $this->normalizeUriPath($this->basepath . $path);
        }
        return $path;
    }


    private function handleRequest(RequestInterface $request):ResponseInterface
    {
        foreach ($this->routes as $route) {
            // add basepath to the route if needed
            $routePath = ($this->basepath != '' && $this->basepath != '/') ? (
                $this->normalizeUriPath($this->basepath . $route->getPath())
            ) : (
                $route->getPath()
            );
            $routeMethods = $route->getMethods();

            $requestUri = $request->getUri();
            $requestMethod = $request->getMethod();

            $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $routePath);
            if (preg_match('#^'.$pattern.'$#', $requestUri, $matches)) {
                if(in_array($requestMethod, $routeMethods)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    foreach ($params as $key => $value) {
                        $request = $request->withAttribute($key, $value);
                    }
                    $response = $route->handle($request);
                    return $response;
                }
                // method not found
                return Response::create()->withStatus(405);
            }
        }
        // route not found
        return Response::create()->withStatus(404);
    }
}
