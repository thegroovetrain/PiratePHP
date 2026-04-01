<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\{RateLimitMiddleware, Request, Response, RequestInterface, ResponseInterface};


final class RateLimitMiddlewareTest extends TestCase
{
    private string $tempDir;


    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/pirate_rate_' . uniqid();
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


    private function makeRequest(string $ip = '127.0.0.1'): RequestInterface
    {
        return Request::createFromArrays([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => $ip,
        ]);
    }


    private function okHandler(): callable
    {
        return function (RequestInterface $req): ResponseInterface {
            return Response::create()->withStatus(200)->withBody('OK');
        };
    }


    public function testUnderLimit(): void
    {
        $middleware = RateLimitMiddleware::create(5, 60, $this->tempDir);
        $request = $this->makeRequest();

        for ($i = 0; $i < 5; $i++) {
            $response = $middleware($request, $this->okHandler());
            $this->assertSame(200, $response->getStatusCode(), "Request $i should be allowed");
        }
    }


    public function testOverLimit(): void
    {
        $middleware = RateLimitMiddleware::create(3, 60, $this->tempDir);
        $request = $this->makeRequest();

        // First 3 should pass
        for ($i = 0; $i < 3; $i++) {
            $response = $middleware($request, $this->okHandler());
            $this->assertSame(200, $response->getStatusCode());
        }

        // 4th should be rate limited
        $response = $middleware($request, $this->okHandler());
        $this->assertSame(429, $response->getStatusCode());
    }


    public function testStorageFailureDegradeGracefully(): void
    {
        $middleware = RateLimitMiddleware::create(1, 60, '/nonexistent/path/that/cannot/be/created');
        $request = $this->makeRequest();

        // Should allow through despite storage failure
        $response = $middleware($request, $this->okHandler());
        $this->assertSame(200, $response->getStatusCode());
    }


    public function testWindowReset(): void
    {
        $middleware = RateLimitMiddleware::create(2, 1, $this->tempDir);
        $request = $this->makeRequest();

        // Use up the limit
        $middleware($request, $this->okHandler());
        $middleware($request, $this->okHandler());

        // 3rd should be blocked
        $response = $middleware($request, $this->okHandler());
        $this->assertSame(429, $response->getStatusCode());

        // Wait for window to expire
        sleep(2);

        // Should be allowed again
        $response = $middleware($request, $this->okHandler());
        $this->assertSame(200, $response->getStatusCode());
    }


    public function testDifferentIpsTrackedSeparately(): void
    {
        $middleware = RateLimitMiddleware::create(1, 60, $this->tempDir);

        $request1 = $this->makeRequest('10.0.0.1');
        $request2 = $this->makeRequest('10.0.0.2');

        // First request from each IP should pass
        $response1 = $middleware($request1, $this->okHandler());
        $response2 = $middleware($request2, $this->okHandler());

        $this->assertSame(200, $response1->getStatusCode());
        $this->assertSame(200, $response2->getStatusCode());

        // Second request from IP1 should be blocked
        $response3 = $middleware($request1, $this->okHandler());
        $this->assertSame(429, $response3->getStatusCode());
    }


    public function testImmutability(): void
    {
        $original = RateLimitMiddleware::create(10, 60, $this->tempDir);
        $modified = $original->withMaxRequests(5);

        $this->assertNotSame($original, $modified);
    }
}
