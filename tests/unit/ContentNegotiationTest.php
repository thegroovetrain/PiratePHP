<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\{ContentNegotiationMiddleware, RendererInterface, Request, Response, RequestInterface, ResponseInterface};


final class ContentNegotiationTest extends TestCase
{
    private function makeRenderer(): RendererInterface
    {
        return new class implements RendererInterface {
            public function render(string $template, array $data = []): string
            {
                return "rendered:{$template}:" . json_encode($data);
            }
        };
    }


    private function makeRequest(string $accept = 'text/html'): RequestInterface
    {
        return Request::createFromArrays([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ], [
            'Accept' => $accept,
        ]);
    }


    public function testJsonAcceptReturnsJson(): void
    {
        $middleware = ContentNegotiationMiddleware::create($this->makeRenderer(), 'default.php');
        $request = $this->makeRequest('application/json');

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            return Response::create()
                ->withStatus(200)
                ->withAttribute('_data', ['name' => 'Alice']);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $this->assertSame('{"name":"Alice"}', $response->getBody());
    }


    public function testHtmlAcceptRendersTemplate(): void
    {
        $middleware = ContentNegotiationMiddleware::create($this->makeRenderer(), 'home.php');
        $request = $this->makeRequest('text/html');

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            return Response::create()
                ->withStatus(200)
                ->withAttribute('_data', ['title' => 'Home']);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/html', $response->getHeader('Content-Type'));
        $this->assertStringContainsString('rendered:home.php:', $response->getBody());
        $this->assertStringContainsString('"title":"Home"', $response->getBody());
    }


    public function testPassthroughWhenNoData(): void
    {
        $middleware = ContentNegotiationMiddleware::create($this->makeRenderer(), 'default.php');
        $request = $this->makeRequest('application/json');

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            return Response::create()->withStatus(200)->withBody('plain response');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('plain response', $response->getBody());
    }


    public function testTemplateRenderingErrorReturns500(): void
    {
        $renderer = new class implements RendererInterface {
            public function render(string $template, array $data = []): string
            {
                throw new \RuntimeException('Template not found');
            }
        };

        $middleware = ContentNegotiationMiddleware::create($renderer, 'missing.php');
        $request = $this->makeRequest('text/html');

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            return Response::create()
                ->withStatus(200)
                ->withAttribute('_data', ['foo' => 'bar']);
        });

        $this->assertSame(500, $response->getStatusCode());
    }


    public function testImmutability(): void
    {
        $original = ContentNegotiationMiddleware::create($this->makeRenderer(), 'default.php');
        $modified = $original->withDefaultTemplate('other.php');

        $this->assertNotSame($original, $modified);
    }
}
