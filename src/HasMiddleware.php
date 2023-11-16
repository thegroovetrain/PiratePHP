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
}