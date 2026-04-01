<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\{StaticFileMiddleware, Request, Response, RequestInterface, ResponseInterface};


final class StaticFileMiddlewareTest extends TestCase
{
    private string $tempDir;


    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/pirate_static_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }


    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }


    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }


    private function makeRequest(string $uri): RequestInterface
    {
        return Request::createFromArrays([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => $uri,
        ]);
    }


    private function passthrough(): callable
    {
        return function (RequestInterface $req): ResponseInterface {
            return Response::create()->withStatus(404)->withBody('Not Found');
        };
    }


    public function testServeFile(): void
    {
        file_put_contents($this->tempDir . '/test.txt', 'Hello World');

        $middleware = StaticFileMiddleware::create($this->tempDir);
        $request = $this->makeRequest('/test.txt');

        $response = $middleware($request, $this->passthrough());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', $response->getHeader('Content-Type'));

        // Body is callable, capture output
        ob_start();
        ($response->getBody())();
        $output = ob_get_clean();
        $this->assertSame('Hello World', $output);
    }


    public function testPathTraversalRejected(): void
    {
        // Create a file outside the base dir
        file_put_contents(sys_get_temp_dir() . '/secret.txt', 'secret data');

        $middleware = StaticFileMiddleware::create($this->tempDir);
        $request = $this->makeRequest('/../secret.txt');

        $response = $middleware($request, $this->passthrough());

        // Should pass through to next middleware (404)
        $this->assertSame(404, $response->getStatusCode());

        @unlink(sys_get_temp_dir() . '/secret.txt');
    }


    public function testFileNotFound(): void
    {
        $middleware = StaticFileMiddleware::create($this->tempDir);
        $request = $this->makeRequest('/nonexistent.txt');

        $response = $middleware($request, $this->passthrough());

        $this->assertSame(404, $response->getStatusCode());
    }


    public function testContentTypeDetection(): void
    {
        file_put_contents($this->tempDir . '/style.css', 'body {}');
        file_put_contents($this->tempDir . '/app.js', 'console.log("hi")');
        file_put_contents($this->tempDir . '/data.json', '{}');
        file_put_contents($this->tempDir . '/page.html', '<html></html>');

        $middleware = StaticFileMiddleware::create($this->tempDir);

        $cases = [
            '/style.css' => 'text/css',
            '/app.js' => 'application/javascript',
            '/data.json' => 'application/json',
            '/page.html' => 'text/html',
        ];

        foreach ($cases as $uri => $expectedType) {
            $request = $this->makeRequest($uri);
            $response = $middleware($request, $this->passthrough());
            $this->assertSame($expectedType, $response->getHeader('Content-Type'), "Failed for $uri");
        }
    }


    public function testDotfilesBlocked(): void
    {
        file_put_contents($this->tempDir . '/.htaccess', 'deny all');
        file_put_contents($this->tempDir . '/.env', 'SECRET=123');

        $middleware = StaticFileMiddleware::create($this->tempDir);

        $request = $this->makeRequest('/.htaccess');
        $response = $middleware($request, $this->passthrough());
        $this->assertSame(404, $response->getStatusCode());

        $request = $this->makeRequest('/.env');
        $response = $middleware($request, $this->passthrough());
        $this->assertSame(404, $response->getStatusCode());
    }


    public function testImmutability(): void
    {
        $original = StaticFileMiddleware::create($this->tempDir);
        $withMimes = $original->withMimeTypes(['custom' => 'text/custom']);

        $this->assertNotSame($original, $withMimes);
    }
}
