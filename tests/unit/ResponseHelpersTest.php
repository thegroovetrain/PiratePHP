<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\Response;


final class ResponseHelpersTest extends MockeryTestCase
{
    public function testJsonBasic():void
    {
        $response = Response::json(['foo' => 'bar']);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"foo":"bar"}', $response->getBody());
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
    }


    public function testJsonWithCustomStatus():void
    {
        $response = Response::json(['created' => true], 201);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"created":true}', $response->getBody());
    }


    public function testJsonEncodingError():void
    {
        // NAN cannot be JSON encoded
        $response = Response::json(NAN);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('JSON encoding error', $response->getBody());
    }


    public function testRedirectDefault():void
    {
        $response = Response::redirect('/login');
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeader('Location'));
    }


    public function testRedirectWithCustomStatus():void
    {
        $response = Response::redirect('/new-location', 301);
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/new-location', $response->getHeader('Location'));
    }


    public function testCallableBody():void
    {
        $response = Response::create()->withBody(function () {
            echo 'callable output';
        });
        $this->assertIsCallable($response->getBody());
        ob_start();
        $response->send();
        $output = ob_get_clean();
        $this->assertSame('callable output', $output);
    }


    public function testCallableBodyDoesNotEcho():void
    {
        $response = Response::create()->withBody(function () {
            // intentionally empty
        });
        ob_start();
        $response->send();
        $output = ob_get_clean();
        $this->assertSame('', $output);
    }


    public function testStringBody():void
    {
        $response = Response::create()->withBody('hello world');
        $this->assertSame('hello world', $response->getBody());
        ob_start();
        $response->send();
        $output = ob_get_clean();
        $this->assertSame('hello world', $output);
    }


    public function testDuplicate424Fixed():void
    {
        $this->assertSame('Failed Dependency', Response::HTTP_STATUS_CODES[424]);
    }
}
