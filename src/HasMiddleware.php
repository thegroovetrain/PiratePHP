<?php declare(strict_types= 1);

namespace thegroovetrain\PiratePHP;


trait HasMiddleware {
    private $middleware = [];

    public function withMiddleware(...$middleware):static
    {
        $new = clone $this;
        $new->middleware = [...$this->middleware, ...$middleware];
        return $new;
    }


    public function getMiddleware():array 
    {
        return $this->middleware;
    }


    public function handle(RequestInterface $request):ResponseInterface
    {
        return $this->processMiddleware($request, 0);
    }


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

    abstract private function handleRequest(RequestInterface $request):ResponseInterface;
}