<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\{App, Route, Router, Response};


final class AppPrefixTest extends MockeryTestCase
{
    public function testFooDoesNotMatchFoobar():void
    {
        $fooRoute = Route::create()
            ->withPath('/')
            ->withMethods('GET')
            ->withHandler(function ($request) {
                return Response::create()->withBody('foo');
            });
        $foobarRoute = Route::create()
            ->withPath('/')
            ->withMethods('GET')
            ->withHandler(function ($request) {
                return Response::create()->withBody('foobar');
            });

        $fooRouter = Router::create()->withBasePath('/foo')->withRoute($fooRoute);
        $foobarRouter = Router::create()->withBasePath('/foobar')->withRoute($foobarRoute);
        $app = App::create()->withRouter($fooRouter, $foobarRouter);

        // /foobar should match /foobar router, not /foo router
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/foobar';
        ob_start();
        $app->run();
        $output = ob_get_clean();
        $this->assertSame('foobar', $output);
    }


    public function testFooMatchesFooSubpath():void
    {
        $fooRoute = Route::create()
            ->withPath('/baz')
            ->withMethods('GET')
            ->withHandler(function ($request) {
                return Response::create()->withBody('foo/baz');
            });

        $fooRouter = Router::create()->withBasePath('/foo')->withRoute($fooRoute);
        $app = App::create()->withRouter($fooRouter);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/foo/baz';
        ob_start();
        $app->run();
        $output = ob_get_clean();
        $this->assertSame('foo/baz', $output);
    }


    public function testFoobarNotMatchedByFoo():void
    {
        $fooRoute = Route::create()
            ->withPath('/')
            ->withMethods('GET')
            ->withHandler(function ($request) {
                return Response::create()->withBody('foo root');
            });

        $fooRouter = Router::create()->withBasePath('/foo')->withRoute($fooRoute);
        $app = App::create()->withRouter($fooRouter);

        // /foobar should NOT match /foo router
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/foobar';
        ob_start();
        $app->run();
        $output = ob_get_clean();
        // Should get 404 (empty body)
        $this->assertSame('', $output);
    }


    public function testRootRouterMatchesAll():void
    {
        $rootRoute = Route::create()
            ->withPath('/anything')
            ->withMethods('GET')
            ->withHandler(function ($request) {
                return Response::create()->withBody('root');
            });

        $rootRouter = Router::create()->withBasePath('/')->withRoute($rootRoute);
        $app = App::create()->withRouter($rootRouter);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/anything';
        ob_start();
        $app->run();
        $output = ob_get_clean();
        $this->assertSame('root', $output);
    }
}
