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
}