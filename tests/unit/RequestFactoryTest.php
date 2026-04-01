<?php declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryTestCase;
use thegroovetrain\PiratePHP\Request;


final class RequestFactoryTest extends MockeryTestCase
{
    public function testCreateFromArraysBasic():void
    {
        $request = Request::createFromArrays(
            ['q' => 'search'],
            ['name' => 'test'],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/users']
        );
        $this->assertSame(['q' => 'search'], $request->getQueryParams());
        $this->assertSame(['name' => 'test'], $request->getPostData());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/api/users', $request->getUri());
    }


    public function testCreateFromArraysWithHeaders():void
    {
        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'],
            ['Content-Type' => 'application/json', 'X-Custom' => 'value']
        );
        $this->assertSame('application/json', $request->getHeader('Content-Type'));
        $this->assertSame('value', $request->getHeader('X-Custom'));
    }


    public function testCreateFromArraysWithBody():void
    {
        $body = '{"foo":"bar"}';
        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'],
            ['Content-Type' => 'application/json'],
            $body
        );
        $this->assertSame($body, $request->getRawBody());
    }


    public function testGetParsedBodyJson():void
    {
        $body = '{"name":"test","count":42}';
        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'],
            ['Content-Type' => 'application/json'],
            $body
        );
        $parsed = $request->getParsedBody();
        $this->assertSame(['name' => 'test', 'count' => 42], $parsed);
    }


    public function testGetParsedBodyCached():void
    {
        $body = '{"a":1}';
        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'],
            ['Content-Type' => 'application/json'],
            $body
        );
        $first = $request->getParsedBody();
        $second = $request->getParsedBody();
        $this->assertSame($first, $second);
    }


    public function testGetParsedBodyInvalidJson():void
    {
        $body = '{invalid json}';
        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'],
            ['Content-Type' => 'application/json'],
            $body
        );
        $this->assertNull($request->getParsedBody());
    }


    public function testGetParsedBodyNonJson():void
    {
        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/'],
            ['Content-Type' => 'text/html'],
            'some body'
        );
        $this->assertNull($request->getParsedBody());
    }


    public function testQueryStringStripped():void
    {
        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/foo/bar?baz=qux&x=1']
        );
        $this->assertSame('/foo/bar', $request->getUri());
    }


    public function testQueryStringStrippedRootPath():void
    {
        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/?foo=bar']
        );
        $this->assertSame('/', $request->getUri());
    }


    public function testCaseInsensitiveHeaders():void
    {
        $request = Request::createFromArrays(
            [],
            [],
            ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'],
            ['Content-Type' => 'text/html', 'X-CUSTOM-HEADER' => 'value']
        );
        // Headers are normalized to lowercase at storage
        $this->assertSame('text/html', $request->getHeader('content-type'));
        $this->assertSame('text/html', $request->getHeader('Content-Type'));
        $this->assertSame('value', $request->getHeader('x-custom-header'));
        $this->assertSame('value', $request->getHeader('X-Custom-Header'));
    }


    public function testCaseInsensitiveHeadersFromServer():void
    {
        $request = Request::createFromArrays(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/',
                'HTTP_ACCEPT' => 'text/html',
                'CONTENT_TYPE' => 'application/json',
                'CONTENT_LENGTH' => '42',
            ]
        );
        $this->assertSame('text/html', $request->getHeader('accept'));
        $this->assertSame('application/json', $request->getHeader('content-type'));
        $this->assertSame('42', $request->getHeader('content-length'));
    }


    public function testCreateFromArraysDefaultParams():void
    {
        $request = Request::createFromArrays();
        $this->assertSame([], $request->getQueryParams());
        $this->assertSame([], $request->getPostData());
        $this->assertSame('', $request->getRawBody());
    }
}
