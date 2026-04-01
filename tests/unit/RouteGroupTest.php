<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\{Route, RouteGroup, Router, Response, Request};


final class RouteGroupTest extends MockeryTestCase
{
    public function testCreateGroup():void
    {
        $group = RouteGroup::create();
        $this->assertSame('', $group->getPrefix());
        $this->assertSame([], $group->getMiddleware());
        $this->assertSame([], $group->getRoutes());
    }


    public function testWithPrefix():void
    {
        $group = RouteGroup::create()->withPrefix('/api/v1');
        $this->assertSame('/api/v1', $group->getPrefix());
    }


    public function testWithPrefixNormalization():void
    {
        $group = RouteGroup::create()->withPrefix('api/v1/');
        $this->assertSame('/api/v1', $group->getPrefix());
    }


    public function testWithMiddleware():void
    {
        $mw = function ($request, $next) { return $next($request); };
        $group = RouteGroup::create()->withMiddleware($mw);
        $this->assertSame([$mw], $group->getMiddleware());
    }


    public function testWithRoute():void
    {
        $route = Route::create()->withPath('/users')->withMethods('GET');
        $group = RouteGroup::create()->withRoute($route);
        $this->assertSame([$route], $group->getRoutes());
    }


    public function testWithMultipleRoutes():void
    {
        $route1 = Route::create()->withPath('/users')->withMethods('GET');
        $route2 = Route::create()->withPath('/posts')->withMethods('GET');
        $group = RouteGroup::create()->withRoute($route1, $route2);
        $this->assertCount(2, $group->getRoutes());
    }


    public function testRouterWithGroupFlattensPrefixes():void
    {
        $route = Route::create()
            ->withPath('/users')
            ->withMethods('GET')
            ->withHandler(function ($request) {
                return Response::create()->withBody('api users');
            });

        $group = RouteGroup::create()
            ->withPrefix('/api/v1')
            ->withRoute($route);

        $router = Router::create()->withGroup($group);

        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/users']
        );
        $response = $router->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('api users', $response->getBody());
    }


    public function testRouterWithGroupPrependsMiddleware():void
    {
        $order = [];
        $groupMw = function ($request, $next) use (&$order) {
            $order[] = 'group';
            return $next($request);
        };
        $routeMw = function ($request, $next) use (&$order) {
            $order[] = 'route';
            return $next($request);
        };

        $route = Route::create()
            ->withPath('/test')
            ->withMethods('GET')
            ->withMiddleware($routeMw)
            ->withHandler(function ($request) {
                return Response::create()->withBody('ok');
            });

        $group = RouteGroup::create()
            ->withPrefix('/api')
            ->withMiddleware($groupMw)
            ->withRoute($route);

        $router = Router::create()->withGroup($group);

        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/test']
        );
        $response = $router->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['group', 'route'], $order);
    }


    public function testEmptyGroup():void
    {
        $group = RouteGroup::create()->withPrefix('/api');
        $router = Router::create()->withGroup($group);
        $this->assertSame([], $router->getRoutes());
    }


    public function testGroupImmutability():void
    {
        $group1 = RouteGroup::create();
        $group2 = $group1->withPrefix('/api');
        $this->assertSame('', $group1->getPrefix());
        $this->assertSame('/api', $group2->getPrefix());
        $this->assertNotSame($group1, $group2);
    }


    public function testGroupNamedRoutesRegistered():void
    {
        $route = Route::create()
            ->withPath('/users/:id')
            ->withMethods('GET')
            ->withName('api.user')
            ->withHandler(function ($request) {
                return Response::create();
            });

        $group = RouteGroup::create()
            ->withPrefix('/api')
            ->withRoute($route);

        $router = Router::create()->withGroup($group);
        $url = $router->urlFor('api.user', ['id' => '42']);
        $this->assertSame('/api/users/42', $url);
    }
}
