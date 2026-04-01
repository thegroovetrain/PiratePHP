<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\{SessionMiddleware, PhpSession, Request, Response, RequestInterface, ResponseInterface};


/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class SessionMiddlewareTest extends TestCase
{
    public function testSessionStartAndReadLifecycle(): void
    {
        $middleware = SessionMiddleware::create();
        $request = Request::createFromArrays([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]);

        $capturedSession = null;
        $capturedFlash = null;

        $response = $middleware($request, function (RequestInterface $req) use (&$capturedSession, &$capturedFlash): ResponseInterface {
            $capturedSession = $req->getAttribute(SessionMiddleware::ATTR_SESSION);
            $capturedFlash = $req->getAttribute(SessionMiddleware::ATTR_FLASH);
            return Response::create()->withStatus(200);
        });

        $this->assertInstanceOf(PhpSession::class, $capturedSession);
        $this->assertInstanceOf(PhpSession::class, $capturedFlash);
        $this->assertSame(200, $response->getStatusCode());
    }


    public function testSessionWriteBack(): void
    {
        $middleware = SessionMiddleware::create();
        $request = Request::createFromArrays([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]);

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            return Response::create()
                ->withStatus(200)
                ->withAttribute(SessionMiddleware::ATTR_SESSION_WRITES, ['user' => 'alice']);
        });

        $this->assertSame(200, $response->getStatusCode());
        // Session should have been written — verify via $_SESSION
        // (session_write_close already called, but $_SESSION still available)
        $this->assertSame('alice', $_SESSION['user'] ?? null);
    }


    public function testFlashWriteBack(): void
    {
        $middleware = SessionMiddleware::create();
        $request = Request::createFromArrays([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]);

        $response = $middleware($request, function (RequestInterface $req): ResponseInterface {
            return Response::create()
                ->withStatus(200)
                ->withAttribute(SessionMiddleware::ATTR_FLASH_WRITES, ['success' => 'Saved!']);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['success' => 'Saved!'], $_SESSION['_flash'] ?? null);
    }


    public function testImmutability(): void
    {
        $original = SessionMiddleware::create();
        $withParams = $original->withCookieParams(['secure' => true]);

        $this->assertNotSame($original, $withParams);
    }


    public function testFlashReadFromPriorSession(): void
    {
        // Start a session and set flash data as if from a prior request
        session_start();
        $_SESSION['_flash'] = ['message' => 'Hello from last request'];
        session_write_close();

        $middleware = SessionMiddleware::create();
        $request = Request::createFromArrays([], [], [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ]);

        $capturedFlash = null;

        $response = $middleware($request, function (RequestInterface $req) use (&$capturedFlash): ResponseInterface {
            $capturedFlash = $req->getAttribute(SessionMiddleware::ATTR_FLASH);
            return Response::create()->withStatus(200);
        });

        $this->assertInstanceOf(PhpSession::class, $capturedFlash);
        $this->assertSame('Hello from last request', $capturedFlash->get('message'));

        // Flash should be cleared from session after reading
        $this->assertArrayNotHasKey('_flash', $_SESSION);
    }
}
