<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class Route implements RouteInterface
{
    use HasMiddleware;

    private string|null $path;
    private $handler;
    private array $methods;


    private function __construct(
        string|null $path = null,
        callable|null $handler = null, 
        array $methods = [], 
        array $middleware = []
    )
    {
        $this->path = $path;
        $this->handler = $handler;
        $this->methods = $methods;
        $this->middleware = $middleware;
    }


    public static function create():static
    {
        return new static();
    }


    public function withPath(string $path):static
    {
        $new = clone $this;
        $new->path = $path;
        return $new;
    }


    public function withHandler(callable $handler):static
    {
        $new = clone $this;
        $new->handler = $handler;
        return $new;
    }


    public function withMethods(string ...$methods):static
    {
        $new = clone $this;
        $new->methods = [...$this->methods, ...$methods];
        return $new;
    }


    public function getPath():string
    {
        return $this->path ?? '';
    }


    public function getHandler():mixed
    {
        return $this->handler;
    }


    public function getMethods():array
    {
        return $this->methods;
    }


    private function handleRequest(RequestInterface $request):ResponseInterface
    {
        return call_user_func($this->handler, $request);
    }
}