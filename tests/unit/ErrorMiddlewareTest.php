<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\{ErrorMiddleware, Request, Response, RequestInterface, ResponseInterface};


final class ErrorMiddlewareTest extends TestCase
{
    private function makeRequest(string $method = 'GET', string $uri = '/'): RequestInterface
    {
        return Request::createFromArrays([], [], [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $uri,
        ]);
    }


    public function testPassthrough(): void
    {
        $middleware = ErrorMiddleware::create();
        $request = $this->makeRequest();

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            return Response::create()->withStatus(200)->withBody('OK');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getBody());
    }


    public function testExceptionReturns500(): void
    {
        $middleware = ErrorMiddleware::create();
        $request = $this->makeRequest();

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            throw new \RuntimeException('Something went wrong');
        });

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getBody());
    }


    public function testCustomErrorHandler(): void
    {
        $middleware = ErrorMiddleware::create()
            ->withErrorHandler(function (\Throwable $e, RequestInterface $req): ResponseInterface {
                return Response::create()
                    ->withStatus(503)
                    ->withBody('Custom: ' . $e->getMessage());
            });

        $request = $this->makeRequest();

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            throw new \RuntimeException('Oops');
        });

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('Custom: Oops', $response->getBody());
    }


    public function testCustomHandlerThrowsReturnsGeneric500(): void
    {
        $middleware = ErrorMiddleware::create()
            ->withErrorHandler(function (\Throwable $e, RequestInterface $req): ResponseInterface {
                throw new \RuntimeException('Handler also fails');
            });

        $request = $this->makeRequest();

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            throw new \RuntimeException('Original error');
        });

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getBody());
    }


    public function testImmutability(): void
    {
        $original = ErrorMiddleware::create();
        $withHandler = $original->withErrorHandler(function (\Throwable $e, RequestInterface $req): ResponseInterface {
            return Response::create()->withStatus(503);
        });

        $this->assertNotSame($original, $withHandler);

        // Original should still return generic 500
        $request = $this->makeRequest();
        $response = $original($request, function (RequestInterface $req): ResponseInterface {
            throw new \RuntimeException('test');
        });
        $this->assertSame(500, $response->getStatusCode());
    }
}
