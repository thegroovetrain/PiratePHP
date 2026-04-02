<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\{Route, Router, Response, Request};


final class RouteParamsTest extends MockeryTestCase
{
    public function testParamsFlowToAttributes():void
    {
        $capturedRequest = null;
        $route = Route::create()
            ->withPath('/users/:id')
            ->withMethods('GET')
            ->withHandler(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return Response::create()->withBody('ok');
            });

        $router = Router::create()->withRoute($route);

        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users/42']
        );
        $response = $router->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('42', $capturedRequest->getAttribute('id'));
    }


    public function testMultipleParams():void
    {
        $capturedRequest = null;
        $route = Route::create()
            ->withPath('/users/:userId/posts/:postId')
            ->withMethods('GET')
            ->withHandler(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return Response::create()->withBody('ok');
            });

        $router = Router::create()->withRoute($route);

        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users/5/posts/123']
        );
        $response = $router->handle($request);
        $this->assertSame('5', $capturedRequest->getAttribute('userId'));
        $this->assertSame('123', $capturedRequest->getAttribute('postId'));
    }


    public function testParamValidationValid():void
    {
        $route = Route::create()->withPath('/users/:id');
        $this->assertSame('/users/:id', $route->getPath());
    }


    public function testParamValidationUnderscoreStart():void
    {
        $route = Route::create()->withPath('/users/:_id');
        $this->assertSame('/users/:_id', $route->getPath());
    }


    public function testParamValidationInvalidStartsWithDigit():void
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::create()->withPath('/users/:1id');
    }


    public function testParamValidationInvalidHyphen():void
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::create()->withPath('/users/:foo-bar');
    }


    public function testHandlerConvertedToClosure():void
    {
        $route = Route::create()->withHandler(function ($request) {
            return Response::create();
        });
        $this->assertInstanceOf(\Closure::class, $route->getHandler());
    }


    public function testNamedRouteBasic():void
    {
        $route = Route::create()
            ->withPath('/users')
            ->withName('users.index');
        $this->assertSame('users.index', $route->getName());
    }


    public function testNamedRouteDefaultNull():void
    {
        $route = Route::create();
        $this->assertNull($route->getName());
    }


    public function testNamedRouteImmutability():void
    {
        $route1 = Route::create()->withName('foo');
        $route2 = $route1->withName('bar');
        $this->assertSame('foo', $route1->getName());
        $this->assertSame('bar', $route2->getName());
        $this->assertNotSame($route1, $route2);
    }


    public function testRouterUrlFor():void
    {
        $route = Route::create()
            ->withPath('/users/:id')
            ->withMethods('GET')
            ->withName('user.show')
            ->withHandler(function ($request) {
                return Response::create();
            });

        $router = Router::create()->withRoute($route);
        $url = $router->urlFor('user.show', ['id' => '42']);
        $this->assertSame('/users/42', $url);
    }


    public function testRouterUrlForWithBasePath():void
    {
        $route = Route::create()
            ->withPath('/users/:id')
            ->withMethods('GET')
            ->withName('user.show')
            ->withHandler(function ($request) {
                return Response::create();
            });

        $router = Router::create()->withBasePath('/api/v1')->withRoute($route);
        $url = $router->urlFor('user.show', ['id' => '42']);
        $this->assertSame('/api/v1/users/42', $url);
    }


    public function testRouterUrlForNotFound():void
    {
        $router = Router::create();
        $this->expectException(\RuntimeException::class);
        $router->urlFor('nonexistent');
    }


    public function testRouterUrlForMultipleParams():void
    {
        $route = Route::create()
            ->withPath('/users/:userId/posts/:postId')
            ->withMethods('GET')
            ->withName('user.post')
            ->withHandler(function ($request) {
                return Response::create();
            });

        $router = Router::create()->withRoute($route);
        $url = $router->urlFor('user.post', ['userId' => '5', 'postId' => '10']);
        $this->assertSame('/users/5/posts/10', $url);
    }
}
