<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class Router implements RouterInterface
{
    use HasMiddleware;

    private string $basepath;
    private array $routes = [];


    private function __construct(string $basepath="/")
    {
        $this->basepath = $basepath;
    }


    public static function create():static
    {
        return new static();
    }


    public function withBasePath(string $basepath):static
    {
        $new = clone $this;
        $new->basepath = $basepath;
        return $new;
    }


    public function withRoute(RouteInterface ...$route):static
    {
        $new = clone $this;
        $new->routes = [...$this->routes, ...$route];
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
    
    
    private function handleRequest(RequestInterface $request):ResponseInterface
    {
        foreach ($this->routes as $route) {
            // add basepath to the route if needed
            $routePath = ($this->basepath != '' && $this->basepath != '/') ? (
                $this->basepath . $route->getPath()
            ) : (
                $route->getPath()
            );
            $routeMethods = $route->getMethods();

            $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/\+)', $routePath);
            if (preg_match('#^'.$pattern.'$#', $request->getUri(), $matches)) {
                if(in_array($request->getMethod(), $routeMethods)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $params = [
                        'request' => $request,
                        ...$params,
                    ];
                    return $route->handle($request);
                }
                // method not found
                return Response::create()->withStatus(405);
            }
        }
        // route not found
        return Response::create()->withStatus(404);
    }
}