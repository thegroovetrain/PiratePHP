<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class RouteGroup
{
    use HasNormalizeUriPath;

    private string $prefix;
    private array $middleware;
    private array $routes;


    private function __construct()
    {
        $this->prefix = '';
        $this->middleware = [];
        $this->routes = [];
    }


    public static function create():static
    {
        return new static();
    }


    public function withPrefix(string $prefix):static
    {
        $new = clone $this;
        $new->prefix = $this->normalizeUriPath($prefix);
        return $new;
    }


    public function withMiddleware(callable ...$middleware):static
    {
        $new = clone $this;
        $new->middleware = [...$this->middleware, ...$middleware];
        return $new;
    }


    public function withRoute(RouteInterface ...$route):static
    {
        $new = clone $this;
        $new->routes = [...$this->routes, ...$route];
        return $new;
    }


    public function getPrefix():string
    {
        return $this->prefix;
    }


    public function getMiddleware():array
    {
        return $this->middleware;
    }


    public function getRoutes():array
    {
        return $this->routes;
    }
}
