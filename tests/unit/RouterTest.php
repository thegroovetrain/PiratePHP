<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\{
    Route, RouteInterface,
    Router, RouterInterface,
    Response, ResponseInterface,
    Request, RequestInterface,
};


final class RouterTest extends MockeryTestCase
{
    protected function mockeryTestSetUp():void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }
    

    public function testCreate():void
    {
        $router = Router::create();
        $this->assertInstanceOf(RouterInterface::class, $router);
    }

    public function testWithBasePath():void
    {
        $router = Router::create();
        $this->assertSame('/', $router->getBasePath());
        $router = $router->withBasePath('/foo');
        $this->assertSame('/foo', $router->getBasePath());
    }

    public function testWithRoute():void
    {
        $route = Route::create()->withPath('/foo');
        $router = Router::create()->withRoute($route);
        $this->assertSame([$route], $router->getRoutes());
        $this->assertSame(1, count($router->getRoutes()));
    }


    public function testHandle():void
    {
        $request = Request::create();
        $router = Router::create()->withRoute(
            Route::create()->withPath('/')->withMethods('GET')->withHandler(function ($request) { 
                return Response::create()->withBody('foo');
            }),
        );
        $response = $router->handle($request);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }
}