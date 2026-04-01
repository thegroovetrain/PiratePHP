<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class Route implements RouteInterface
{
    use HasMiddleware;
    use HasNormalizeUriPath;

    private string|null $path;
    private \Closure|null $handler;
    private array $methods;
    private string|null $name;


    private function __construct(
        string|null $path = null,
        \Closure|null $handler = null,
        array $methods = [],
        array $middleware = [],
        string|null $name = null
    )
    {
        $this->path = $path;
        $this->handler = $handler;
        $this->methods = $methods;
        $this->middleware = $middleware;
        $this->name = $name;
    }


    public static function create():static
    {
        return new static();
    }


    public function withPath(string $path):static
    {
        $new = clone $this;
        // Validate and build the normalized path
        $normalized = $this->normalizeUriPath($path);

        // Validate param names and escape literal segments
        $segments = explode('/', $normalized);
        $validatedSegments = [];
        foreach ($segments as $segment) {
            if ($segment === '') {
                $validatedSegments[] = $segment;
                continue;
            }
            if (str_starts_with($segment, ':')) {
                $paramName = substr($segment, 1);
                if (!preg_match('/^[a-zA-Z_]\w*$/', $paramName)) {
                    throw new \InvalidArgumentException("Invalid route parameter name: '{$paramName}'");
                }
                $validatedSegments[] = $segment;
            } else {
                $validatedSegments[] = $segment;
            }
        }

        $new->path = implode('/', $validatedSegments);
        return $new;
    }


    public function withHandler(callable $handler):static
    {
        $new = clone $this;
        $new->handler = \Closure::fromCallable($handler);
        return $new;
    }


    public function withMethods(string ...$methods):static
    {
        $new = clone $this;
        $new->methods = [...$this->methods, ...$methods];
        return $new;
    }


    public function withName(string $name):static
    {
        $new = clone $this;
        $new->name = $name;
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


    public function getName():string|null
    {
        return $this->name;
    }


    private function handleRequest(RequestInterface $request):ResponseInterface
    {
        return call_user_func($this->handler, $request);
    }
}
