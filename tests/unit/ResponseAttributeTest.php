<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\Response;


final class ResponseAttributeTest extends MockeryTestCase
{
    public function testWithAttribute():void
    {
        $response = Response::create();
        $response2 = $response->withAttribute('foo', 'bar');
        $this->assertSame('bar', $response2->getAttribute('foo'));
        $this->assertNull($response->getAttribute('foo'));
        $this->assertNotSame($response, $response2);
    }


    public function testGetAttributeReturnsNullForMissing():void
    {
        $response = Response::create();
        $this->assertNull($response->getAttribute('nonexistent'));
    }


    public function testWithoutAttribute():void
    {
        $response = Response::create()
            ->withAttribute('foo', 'bar')
            ->withAttribute('baz', 'qux');
        $response2 = $response->withoutAttribute('foo');
        $this->assertNull($response2->getAttribute('foo'));
        $this->assertSame('qux', $response2->getAttribute('baz'));
        $this->assertSame('bar', $response->getAttribute('foo'));
    }


    public function testWithoutMultipleAttributes():void
    {
        $response = Response::create()
            ->withAttribute('a', '1')
            ->withAttribute('b', '2')
            ->withAttribute('c', '3');
        $response2 = $response->withoutAttribute('a', 'b');
        $this->assertNull($response2->getAttribute('a'));
        $this->assertNull($response2->getAttribute('b'));
        $this->assertSame('3', $response2->getAttribute('c'));
    }


    public function testWithAttributeOverwrite():void
    {
        $response = Response::create()
            ->withAttribute('foo', 'bar')
            ->withAttribute('foo', 'baz');
        $this->assertSame('baz', $response->getAttribute('foo'));
    }


    public function testWithAttributeMixedTypes():void
    {
        $response = Response::create()
            ->withAttribute('string', 'hello')
            ->withAttribute('int', 42)
            ->withAttribute('array', [1, 2, 3])
            ->withAttribute('null', null);
        $this->assertSame('hello', $response->getAttribute('string'));
        $this->assertSame(42, $response->getAttribute('int'));
        $this->assertSame([1, 2, 3], $response->getAttribute('array'));
        $this->assertNull($response->getAttribute('null'));
    }
}
