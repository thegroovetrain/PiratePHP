<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use EricSeibt\PiratePHP\{RequestInterface, Request};


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
        $_SERVER['HTTP_FOO_HEADER'] = ['foo'];

        $this->request = new Request();
    }


    public function testGetAllQueryParams():void
    {
        $expected = [
            'foo' => 'foovalue',
            'bar' => 'barvalue',
        ];
        $this->assertSame($expected, $this->request->getAllQueryParams());
        $this->assertSame(count($expected), count($this->request->getAllQueryParams()));
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


    public function testGetAllPostData():void
    {
        $expected = [
            'foo' => 'foovalue',
            'bar' => 'barvalue',
        ];
        $this->assertSame($expected, $this->request->getAllPostData());
        $this->assertSame(count($expected), count($this->request->getAllPostData()));
    }


    public function testGetPostData():void
    {
        $expected = [
            'foo' => 'foovalue',
            'bar' => 'barvalue',
            'bat' => null,
        ];
        foreach($expected as $key => $expectedValue) {
            $this->assertSame($expectedValue, $this->request->getPostData($key));
        }
        // test default
        $this->assertSame('', $this->request->getPostData('baz', ''));
    }


    public function testGetAllServerData():void
    {
        $this->assertSame($_SERVER, $this->request->getAllServerData());
        $this->assertSame(count($_SERVER), count($this->request->getAllServerData()));
    }
}