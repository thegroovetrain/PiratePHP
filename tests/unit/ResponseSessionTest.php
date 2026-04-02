<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\{Response, PhpSession, SessionInterface};


final class ResponseSessionTest extends TestCase
{
    public function testWithSessionReturnsClone(): void
    {
        $r1 = Response::create();
        $session = new PhpSession(['user' => 'Blackbeard']);
        $r2 = $r1->withSession($session);
        $this->assertNotSame($r1, $r2);
        $this->assertNull($r1->getSession());
        $this->assertInstanceOf(SessionInterface::class, $r2->getSession());
        $this->assertSame('Blackbeard', $r2->getSession()->get('user'));
    }

    public function testWithFlashReturnsClone(): void
    {
        $r1 = Response::create();
        $r2 = $r1->withFlash(['message' => 'Hello!']);
        $this->assertNotSame($r1, $r2);
        $this->assertNull($r1->getFlashData());
        $this->assertSame(['message' => 'Hello!'], $r2->getFlashData());
    }

    public function testSessionDefaultIsNull(): void
    {
        $r = Response::create();
        $this->assertNull($r->getSession());
        $this->assertNull($r->getFlashData());
    }

    public function testWithSessionAndFlashTogether(): void
    {
        $session = new PhpSession(['logged_in' => true]);
        $r = Response::redirect('/dashboard')
            ->withSession($session)
            ->withFlash(['welcome' => 'Hello!']);
        $this->assertSame(302, $r->getStatusCode());
        $this->assertTrue($r->getSession()->get('logged_in'));
        $this->assertSame(['welcome' => 'Hello!'], $r->getFlashData());
    }

    public function testImmutableSessionOnResponse(): void
    {
        $s1 = new PhpSession(['a' => 1]);
        $s2 = $s1->with('b', 2);
        $r1 = Response::create()->withSession($s1);
        $r2 = Response::create()->withSession($s2);
        $this->assertSame(1, $r1->getSession()->get('a'));
        $this->assertNull($r1->getSession()->get('b'));
        $this->assertSame(2, $r2->getSession()->get('b'));
    }
}
