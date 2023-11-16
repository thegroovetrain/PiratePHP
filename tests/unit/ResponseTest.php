<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\{Response, ResponseInterface};


final class ResponseTest extends MockeryTestCase
{
    public function testPrepare():void
    {
        $response = Response::create();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(Response::HTTP_STATUS_CODES[200], $response->getStatusMessage());
        $this->assertSame([], $response->getHeaders());
        $this->assertSame('', $response->getBody());
    }


    public function testWithStatusDefaultMessage():void
    {
        $response = Response::create();
        for($i = 1; $i <= 600; $i++) {
            $response = $response->withStatus($i);
            $expectedMessage = Response::HTTP_STATUS_CODES[$i] ?? '';
            $this->assertSame($expectedMessage, $response->getStatusMessage());
        }
    }


    public function testWithStatusCustomMessage():void
    {
        $response = Response::create();
        for($i = 1; $i <= 600; $i++) {
            $expectedMessage = "foo<$i>";
            $response = $response->withStatus($i, $expectedMessage);
            $this->assertSame($expectedMessage, $response->getStatusMessage());   
        }
    }


    public function testWithBody():void
    {
        $response = Response::create();
        $response = $response->withBody("foo");
        $this->assertSame("foo", $response->getBody());
    }


    public function testWithHeaders():void
    {
        $response = Response::create();
        $response = $response->withHeader("Foo", "foovalue");
        $response = $response->withHeaders([
            "Bar" => "barvalue",
            "Baz" => "bazvalue",
            "Bat" => "batvalue",
        ]);
        $this->assertSame([
            "Foo" => "foovalue",
            "Bar" => "barvalue",
            "Baz" => "bazvalue",
            "Bat" => "batvalue",
        ], $response->getHeaders());
        $this->assertSame("foovalue", $response->getHeader('Foo'));
        $response = $response->withoutHeaders('Bar');
        $response = $response->withoutHeaders('Baz', 'Bat');
        $this->assertSame([
            "Foo" => "foovalue",
        ], $response->getHeaders());
        $this->assertSame(null, $response->getHeader("Bar"));
    }


    public function testWithoutHeaders():void
    {
        $response = Response::create();
        $response = $response->withHeaders([
            "Foo" => "foovalue",
            "Bar" => "barvalue",
            "Baz" => "bazvalue",
        ]);
        $response = $response->withoutHeaders("Foo");
        $this->assertNull($response->getHeader("Foo"));
        $response = $response->withoutHeaders("Bar", "Baz");
        $this->assertSame([], $response->getHeaders());
        
    }

    
    public function testSend():void
    {
        $response = Response::create()->withBody("foo");
        ob_start();
        $response->send();
        $output = ob_get_clean();
        $this->assertSame("foo", $output);
    }
}