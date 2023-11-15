<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\{Route, RouteInterface};


final class RouteTest extends MockeryTestCase
{
    public function testCreate():Void
    {
        $route = Route::create();
        $this->assertInstanceOf(RouteInterface::class, $route);
    }

    public function testWithPath():void
    {
        $route = Route::create();
        $route2 = $route->withPath("/foo");
        $this->assertSame("/foo", $route2->getPath());
        $this->assertNotSame($route, $route2);
    }

    public function testWithMethods():void 
    {
        $route = Route::create();
        $route2 = $route->withMethods("GET", "POST");
        $this->assertContains("GET", $route2->getMethods());
        $this->assertContains("POST", $route2->getMethods());
        $this->assertNotSame($route, $route2);
    }

    public function testWithHandler():void
    {
        $route = Route::create();
        $route2 = $route->withHandler(function () { return 'foo'; });
        $this->assertIsCallable($route2->getHandler());
        $this->assertNotSame($route, $route2);
    }

    public function testWithMiddleware():void
    {
        $route = Route::create();
        $middleware = [
            function () { return 'foo'; },
            function () { return 'foo'; },
        ];
        $route2 = $route->withMiddleware(...$middleware);
        $this->assertSame($middleware, $route2->getMiddleware());
        $this->assertNotSame($route, $route2);
    }
}