<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\CookieJar;


final class CookieJarTest extends TestCase
{
    public function testParseCookies(): void
    {
        $jar = CookieJar::fromHeaderString('session=abc123; user=john; theme=dark');

        $this->assertSame('abc123', $jar->get('session'));
        $this->assertSame('john', $jar->get('user'));
        $this->assertSame('dark', $jar->get('theme'));
    }


    public function testGet(): void
    {
        $jar = CookieJar::fromHeaderString('foo=bar');

        $this->assertSame('bar', $jar->get('foo'));
        $this->assertNull($jar->get('missing'));
        $this->assertSame('default', $jar->get('missing', 'default'));
    }


    public function testHas(): void
    {
        $jar = CookieJar::fromHeaderString('exists=yes');

        $this->assertTrue($jar->has('exists'));
        $this->assertFalse($jar->has('nope'));
    }


    public function testAll(): void
    {
        $jar = CookieJar::fromHeaderString('a=1; b=2; c=3');

        $all = $jar->all();
        $this->assertSame('1', $all['a']);
        $this->assertSame('2', $all['b']);
        $this->assertSame('3', $all['c']);
        $this->assertCount(3, $all);
    }


    public function testEmptyCookieString(): void
    {
        $jar = CookieJar::fromHeaderString('');

        $this->assertSame([], $jar->all());
        $this->assertFalse($jar->has('anything'));
    }


    public function testUrlDecodedValues(): void
    {
        $jar = CookieJar::fromHeaderString('name=hello%20world');

        $this->assertSame('hello world', $jar->get('name'));
    }


    public function testCreateEmpty(): void
    {
        $jar = CookieJar::create();

        $this->assertSame([], $jar->all());
    }


    public function testSpacesAroundPairs(): void
    {
        $jar = CookieJar::fromHeaderString('  foo = bar ;  baz = qux  ');

        $this->assertSame('bar', $jar->get('foo'));
        $this->assertSame('qux', $jar->get('baz'));
    }
}
