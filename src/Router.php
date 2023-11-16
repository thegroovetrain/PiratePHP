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


    public static function create(string|null $basepath=null):static
    {
        return new static($basepath);
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


    public function withMiddleware(callable ...$middleware):static
    {
        $new = clone $this;
        $new->middleware = array_merge($new->middleware, ...$middleware);
    }


    public function getBasePath():string
    {
        return $this->basepath;
    }


    public function getRoutes():array
    {
        return $this->routes;
    }
}