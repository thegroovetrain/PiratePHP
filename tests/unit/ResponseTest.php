<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\Response;


final class ResponseTest extends MockeryTestCase
{
    public function testStatusCodes():void
    {
        foreach(Response::HTTP_STATUS_CODES as $code => $message) {
            $response = new Response();
            $response->setStatus($code);
            $this->assertSame($code, $response->getStatusCode());
            $this->assertSame($message, $response->getStatusMessage());
        }
        $response = new Response();
        $response->setStatus(200, "foo");
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("foo", $response->getStatusMessage());
    }


    public function testBody():void
    {
        $response = new Response();
        $this->assertSame('', $response->getBody());
        $response->setBody('foo');
        $this->assertSame('foo', $response->getBody());
    }


    public function testHeaders():void
    {
        $response = new Response();
        $response->addHeader('Foo', 'foovalue');
        $response->addHeaders([
            'Bar' => 'barvalue',
            'Baz' => 'bazvalue',
        ]);
        $this->assertSame([
            'Foo' => 'foovalue',
            'Bar' => 'barvalue',
            'Baz' => 'bazvalue',
        ], $response->getHeaders());
    }

    // TODO: test send()
}