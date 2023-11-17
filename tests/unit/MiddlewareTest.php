<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\{
    HasMiddleware,
    RequestInterface,
    Request,
    ResponseInterface,
    Response
};



class HasMiddlewareTestClass
{
    use HasMiddleware;

    public static function create()
    {
        return new static();
    }

    private function handleRequest(RequestInterface $request):ResponseInterface
    {
        return Response::create();
    }
}


function testMiddleware (RequestInterface $request, callable $next):ResponseInterface {
    $response = $next($request);
    return $response->withHeader('Foo', 'bar');
}


final class MiddlewareTest extends MockeryTestCase
{
    protected $instance;

    protected function mockeryTestSetUp():void
    {
        $this->instance = HasMiddlewareTestClass::create()->withMiddleware('testMiddleware');
    }


    public function testGetMiddleware():void
    {
        $this->assertSame(['testMiddleware'], $this->instance->getMiddleware());
    }


    public function testHandle():void
    {
        $response = $this->instance->handle(Request::create());
        $this->assertSame('bar', $response->getHeader('Foo'));
    }


}