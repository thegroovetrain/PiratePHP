<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\PhpSession;


final class PhpSessionWriteTest extends TestCase
{
    public function testWithAddsKey(): void
    {
        $session = new PhpSession(['a' => 1]);
        $new = $session->with('b', 2);
        $this->assertSame(2, $new->get('b'));
        $this->assertNull($session->get('b'));
    }

    public function testWithOverwritesKey(): void
    {
        $session = new PhpSession(['a' => 1]);
        $new = $session->with('a', 99);
        $this->assertSame(99, $new->get('a'));
        $this->assertSame(1, $session->get('a'));
    }

    public function testWithoutRemovesKey(): void
    {
        $session = new PhpSession(['a' => 1, 'b' => 2]);
        $new = $session->without('a');
        $this->assertFalse($new->has('a'));
        $this->assertTrue($session->has('a'));
        $this->assertTrue($new->has('b'));
    }

    public function testWithoutNonexistentKeyIsNoOp(): void
    {
        $session = new PhpSession(['a' => 1]);
        $new = $session->without('z');
        $this->assertSame(['a' => 1], $new->all());
    }

    public function testChainedWrites(): void
    {
        $session = new PhpSession();
        $new = $session->with('a', 1)->with('b', 2)->with('c', 3)->without('b');
        $this->assertSame(['a' => 1, 'c' => 3], $new->all());
        $this->assertSame([], $session->all());
    }

    public function testImmutability(): void
    {
        $s1 = new PhpSession(['x' => 10]);
        $s2 = $s1->with('y', 20);
        $s3 = $s1->without('x');
        $this->assertNotSame($s1, $s2);
        $this->assertNotSame($s1, $s3);
        $this->assertSame(['x' => 10], $s1->all());
    }
}
