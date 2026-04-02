<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\{Request, SessionInterface, PhpSession};


final class RequestSessionTest extends TestCase
{
    public function testGetSessionReturnsSessionInterface(): void
    {
        $request = Request::createFromArrays(session: ['user' => 'Blackbeard']);
        $session = $request->getSession();
        $this->assertInstanceOf(SessionInterface::class, $session);
        $this->assertSame('Blackbeard', $session->get('user'));
    }

    public function testGetFlashReturnsSessionInterface(): void
    {
        $request = Request::createFromArrays(flash: ['message' => 'Saved!']);
        $flash = $request->getFlash();
        $this->assertInstanceOf(SessionInterface::class, $flash);
        $this->assertSame('Saved!', $flash->get('message'));
    }

    public function testDefaultSessionIsEmpty(): void
    {
        $request = Request::createFromArrays();
        $this->assertSame([], $request->getSession()->all());
        $this->assertSame([], $request->getFlash()->all());
    }

    public function testSessionAndFlashAreIndependent(): void
    {
        $request = Request::createFromArrays(
            session: ['a' => 1],
            flash: ['b' => 2]
        );
        $this->assertTrue($request->getSession()->has('a'));
        $this->assertFalse($request->getSession()->has('b'));
        $this->assertTrue($request->getFlash()->has('b'));
        $this->assertFalse($request->getFlash()->has('a'));
    }
}
