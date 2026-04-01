<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\Response;


final class ResponseHeaderArrayTest extends MockeryTestCase
{
    public function testWithHeaderReplacesValues():void
    {
        $response = Response::create()
            ->withHeader('X-Foo', 'first')
            ->withHeader('X-Foo', 'second');
        $this->assertSame('second', $response->getHeader('X-Foo'));
        $this->assertSame(['second'], $response->getHeaderArray('X-Foo'));
    }


    public function testWithAddedHeaderAppends():void
    {
        $response = Response::create()
            ->withHeader('Set-Cookie', 'a=1')
            ->withAddedHeader('Set-Cookie', 'b=2')
            ->withAddedHeader('Set-Cookie', 'c=3');
        $this->assertSame('a=1', $response->getHeader('Set-Cookie'));
        $this->assertSame(['a=1', 'b=2', 'c=3'], $response->getHeaderArray('Set-Cookie'));
    }


    public function testWithAddedHeaderOnNewName():void
    {
        $response = Response::create()
            ->withAddedHeader('X-New', 'value');
        $this->assertSame('value', $response->getHeader('X-New'));
        $this->assertSame(['value'], $response->getHeaderArray('X-New'));
    }


    public function testGetHeaderArrayReturnsEmptyForMissing():void
    {
        $response = Response::create();
        $this->assertSame([], $response->getHeaderArray('X-Missing'));
    }


    public function testGetHeaderReturnsFirstValue():void
    {
        $response = Response::create()
            ->withAddedHeader('X-Multi', 'first')
            ->withAddedHeader('X-Multi', 'second');
        $this->assertSame('first', $response->getHeader('X-Multi'));
    }


    public function testGetHeadersReturnsArrayFormat():void
    {
        $response = Response::create()
            ->withHeader('Content-Type', 'text/html')
            ->withAddedHeader('Set-Cookie', 'a=1')
            ->withAddedHeader('Set-Cookie', 'b=2');
        $headers = $response->getHeaders();
        $this->assertSame(['text/html'], $headers['Content-Type']);
        $this->assertSame(['a=1', 'b=2'], $headers['Set-Cookie']);
    }


    public function testWithHeadersAcceptsArrayValues():void
    {
        $response = Response::create()->withHeaders([
            'X-Foo' => ['a', 'b'],
            'X-Bar' => 'single',
        ]);
        $this->assertSame(['a', 'b'], $response->getHeaderArray('X-Foo'));
        $this->assertSame(['single'], $response->getHeaderArray('X-Bar'));
    }


    public function testSendWithMultiValueHeaders():void
    {
        $response = Response::create()
            ->withAddedHeader('Set-Cookie', 'a=1')
            ->withAddedHeader('Set-Cookie', 'b=2')
            ->withBody('ok');
        ob_start();
        $response->send();
        $output = ob_get_clean();
        $this->assertSame('ok', $output);
    }


    public function testImmutability():void
    {
        $response1 = Response::create()->withHeader('X-Foo', 'bar');
        $response2 = $response1->withAddedHeader('X-Foo', 'baz');
        $this->assertSame(['bar'], $response1->getHeaderArray('X-Foo'));
        $this->assertSame(['bar', 'baz'], $response2->getHeaderArray('X-Foo'));
        $this->assertNotSame($response1, $response2);
    }
}
