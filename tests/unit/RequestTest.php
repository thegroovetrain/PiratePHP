<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\{RequestInterface, Request};


final class RequestTest extends MockeryTestCase
{
    private RequestInterface $request;

    protected function mockeryTestSetUp()
    {
        $_GET['foo'] = 'foovalue';
        $_GET['bar'] = 'barvalue';
        $_POST['foo'] = 'foovalue';
        $_POST['bar'] = 'barvalue';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/foo/bar';
        $_SERVER['HTTP_FOO_HEADER'] = 'foo';

        $this->request = Request::create();
    }


    public function testGetQueryParams():void
    {
        $expected = [
            'foo' => 'foovalue',
            'bar' => 'barvalue',
        ];
        $this->assertSame($expected, $this->request->getQueryParams());
        $this->assertSame(count($expected), count($this->request->getQueryParams()));
    }


    public function testGetQueryParam():void
    {
        $expected = [
            'foo' => 'foovalue',
            'bar' => 'barvalue',
            'bat' => null,
        ];
        foreach($expected as $key => $expectedValue) {
            $this->assertSame($expectedValue, $this->request->getQueryParam($key));
        }
        // test default
        $this->assertSame('', $this->request->getQueryParam('baz', ''));
    }


    public function testGetPostData():void
    {
        $expected = [
            'foo' => 'foovalue',
            'bar' => 'barvalue',
        ];
        $this->assertSame($expected, $this->request->getPostData());
        $this->assertSame(count($expected), count($this->request->getPostData()));
    }


    public function testGetPostDatum():void
    {
        $expected = [
            'foo' => 'foovalue',
            'bar' => 'barvalue',
            'bat' => null,
        ];
        foreach($expected as $key => $expectedValue) {
            $this->assertSame($expectedValue, $this->request->getPostDatum($key));
        }
        // test default
        $this->assertSame('', $this->request->getPostDatum('baz', ''));
    }


    public function testGetServerData():void
    {
        $this->assertSame($_SERVER, $this->request->getServerData());
        $this->assertSame(count($_SERVER), count($this->request->getServerData()));
    }


    public function testGetServerDatum():void
    {
        $expected = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/foo/bar',
            'HTTP_BAZ' => null,
        ];
        foreach($expected as $key => $expectedValue) {
            $this->assertSame($expectedValue, $this->request->getServerDatum($key));
        }
        // test default
        $this->assertSame('', $this->request->getServerDatum('HTTP_BAT', ''));
    }


    public function testGetHeaders():void
    {
        $expected = [
            'Foo-Header' => 'foo',
        ];
        $this->assertSame($expected, $this->request->getHeaders());
        $this->assertSame(count($expected), count($this->request->getHeaders()));
    }


    public function testGetHeader():void
    {
        $expected = [
            'Foo-Header' => 'foo',
            'Bar-Header' => null,
        ];
        foreach($expected as $key => $expectedValue) {
            $this->assertSame($expectedValue, $this->request->getHeader($key));
        }
        // test default
        $this->assertSame('', $this->request->getHeader($key, ''));
    }


    public function testGetUri():void
    {
        $this->assertSame('/foo/bar', $this->request->getUri());
    }


    public function testGetMethod():void
    {
        $this->assertSame('GET', $this->request->getMethod());
    }


    public function testWithAttribute():void
    {
        $request = $this->request->withAttribute('foo', 'bar');
        $this->assertSame('bar', $request->getAttribute('foo'));
        $request = $request->withoutAttributes('foo');
        $this->assertNull($request->getAttribute('foo'));
    }
}