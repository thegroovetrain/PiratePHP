<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\{
    App, AppInterface,
    Route, RouteInterface,
    Router, RouterInterface,
    Request, RequestInterface,
    Response, ResponseInterface,
};


class AppTest extends MockeryTestCase
{
    public function testCreate():void
    {
        $app = App::create();
        $this->assertInstanceOf(AppInterface::class, $app);
    }


    public function testWithRouter():void
    {
        $router = Router::create();
        $app = App::create()->withRouter($router);
        $this->assertSame([$router], $app->getRouters());
        $this->assertSame(count([$router]), count($app->getRouters()));
    }


    public function testGetSortedRouters():void
    {
        $router1 = Router::create()->withBasePath('/');
        $router2 = Router::create()->withBasePath('/foo/bar');
        $router3 = Router::create()->withBasePath('/foo');
        $app = App::create()->withRouter($router1, $router2, $router3);
        $getRouters = $app->getRouters();
        $this->assertSame([$router1, $router2, $router3], $getRouters);
        $this->assertNotSame($getRouters, $app->getSortedRouters());
        $this->assertSame([$router2, $router3, $router1], $app->getSortedRouters());
    }


    public function testRun():void
    {
        $route1a = Route::create()->withPath('/')->withMethods('GET')->withHandler(function ($request) { return Response::create()->withStatus(200)->withBody('hello'); });
        $router1 = Router::create()->withBasePath('/')->withRoute($route1a);
        $route2a = Route::create()->withPath('/')->withMethods('GET')->withHandler(function ($request) { return Response::create()->withStatus(200)->withBody('foobar'); });
        $route2b = Route::create()->withPath('/bat')->withMethods('GET')->withHandler(function ($request) { return Response::create()->withStatus(200)->withBody('foobarbat'); });
        $router2 = Router::create()->withBasePath('/foo/bar')->withRoute($route2a, $route2b);
        $route3a = Route::create()->withPath('/baz')->withMethods('GET')->withHandler(function ($request) { return Response::create()->withStatus(200)->withBody('foobaz'); });
        $router3 = Router::create()->withBasePath('/foo')->withRoute($route3a);
        $app = App::create()->withRouter($router1, $router2, $router3);

        $scenarios = [
            [
                'uri' => '',
                'body' => 'hello',
                'status' => 200,
            ],
            [
                'uri' => '/',
                'body' => 'hello',
                'status' => 200,
            ],
            [
                'uri' => '/foo/bar',
                'body' => 'foobar',
                'status' => 200,
            ],
            [
                'uri' => '/foo/bar/',
                'body' => 'foobar',
                'status' => 200,
            ],
            [
                'uri' => '/foo/bar/bat',
                'body' => 'foobarbat',
                'status' => 200,
            ],
            [
                'uri' => '/foo/bar/bat/',
                'body' => 'foobarbat',
                'status' => 200,
            ],
            [
                'uri' => '/foo/baz',
                'body' => 'foobaz',
                'status' => 200,
            ],
            [
                'uri' => '/foo/baz/',
                'body' => 'foobaz',
                'status' => 200,
            ],
            [
                'uri' => '/bax',
                'body' => '',
                'status' => 404,
            ],
            [
                'uri' => '/bax/',
                'body' => '',
                'status' => 404,
            ],
        ];
        

        $_SERVER['REQUEST_METHOD'] = 'GET';

        foreach($scenarios as $scenario) {
            print_r($scenario);
            ob_start();
            $_SERVER['REQUEST_URI'] = $scenario['uri'];
            $app->run();
            $output = ob_get_clean();
            $this->assertSame($scenario['body'], $output);   
        }
    }
}