<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;
use EricSeibt\PiratePHP\RequestInterface;


class Route implements RouteInterface
{
    protected string $path;
    protected string $handler;

    function __construct(string $path, string $handler) {
        $this->path = $path;
        $this->handler = $handler;
    }

    public function checkMatch(RequestInterface $request):bool
    {

    }
}


class DirectRoute extends Route implements DirectRouteInterface
{
    protected string $method;

    function __construct(string $path, string $handler, string $method='GET')
    {
        $this->method = $method;
        parent::__construct($path, $handler);
    }

    public function checkMatch(RequestInterface $request):bool
    {
        
    }
}