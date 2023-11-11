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


    public function testGetPath():void
    {
        $this->assertSame('/foo/bar', $this->request->getPath());
    }


    public function testGetMethod():void
    {
        $this->assertSame('GET', $this->request->getMethod());
    }


    public function testIsMethod():void
    {
        $reflection = new ReflectionClass('EricSeibt\PiratePHP\Request');
        $constants = $reflection->getConstants();
        $constants = array_filter($constants, function ($value, $key) {
            return substr($key, 0, 5) === 'HTTP_';
        }, ARRAY_FILTER_USE_BOTH);

        for($i = 0; $i < count($constants); $i++) {
            // method to test
            $ikey = array_keys($constants)[$i];
            $ivalue = $constants[$ikey];
            $testMethodName = 'is' . ucwords(strtolower($ivalue));
            $reflectionTestMethod = new ReflectionMethod('EricSeibt\PiratePHP\Request', $testMethodName);

            for($j = 0; $j < count($constants); $j++) {
                $jkey = array_keys($constants)[$j];
                $jvalue = $constants[$jkey];

                $_SERVER['REQUEST_METHOD'] = $jvalue;
                $request = new Request();

                $matches = $jvalue === $ivalue;
                if($matches) {
                    $this->assertTrue($reflectionTestMethod->invoke($request));
                } else {
                    $this->assertFalse($reflectionTestMethod->invoke($request));
                }
            }
        }
    }
}