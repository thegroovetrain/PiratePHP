<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\PhpSession;


final class PhpSessionTest extends TestCase
{
    public function testGetWithDefault(): void
    {
        $session = new PhpSession(['foo' => 'bar']);

        $this->assertSame('bar', $session->get('foo'));
        $this->assertNull($session->get('missing'));
        $this->assertSame('default', $session->get('missing', 'default'));
    }


    public function testHas(): void
    {
        $session = new PhpSession(['exists' => 'yes', 'null_val' => null]);

        $this->assertTrue($session->has('exists'));
        $this->assertTrue($session->has('null_val'));
        $this->assertFalse($session->has('nope'));
    }


    public function testAll(): void
    {
        $data = ['a' => 1, 'b' => 2, 'c' => 3];
        $session = new PhpSession($data);

        $this->assertSame($data, $session->all());
    }


    public function testEmptySession(): void
    {
        $session = new PhpSession();

        $this->assertSame([], $session->all());
        $this->assertFalse($session->has('anything'));
        $this->assertNull($session->get('anything'));
    }
}
