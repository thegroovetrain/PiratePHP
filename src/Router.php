<?php declare(strict_types=1);

namespace EricSeibt\PiratePHP;
use Ericseibt\PiratePHP\{Route, RouteInterface};


class Router implements EricSeibt\PiratePHP\RouterInterface
{
    private array $routes = [];
}