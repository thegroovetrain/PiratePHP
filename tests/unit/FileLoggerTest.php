<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\FileLogger;


final class FileLoggerTest extends TestCase
{
    private string $tempFile;


    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'pirate_log_');
    }


    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            @unlink($this->tempFile);
        }
    }


    public function testWriteToFile(): void
    {
        $logger = FileLogger::create($this->tempFile);
        $logger->log('Hello World');

        $content = file_get_contents($this->tempFile);
        $this->assertSame('Hello World' . PHP_EOL, $content);
    }


    public function testMultipleWrites(): void
    {
        $logger = FileLogger::create($this->tempFile);
        $logger->log('Line 1');
        $logger->log('Line 2');

        $content = file_get_contents($this->tempFile);
        $this->assertSame('Line 1' . PHP_EOL . 'Line 2' . PHP_EOL, $content);
    }


    public function testNewlineSanitization(): void
    {
        $logger = FileLogger::create($this->tempFile);
        $logger->log("line1\nline2\rline3");

        $content = file_get_contents($this->tempFile);
        $this->assertSame('line1 line2 line3' . PHP_EOL, $content);
    }


    public function testNotWritablePathSwallowsError(): void
    {
        $logger = FileLogger::create('/nonexistent/path/that/does/not/exist/log.txt');

        // Should not throw
        $logger->log('This will fail silently');

        $this->assertTrue(true); // If we get here, error was swallowed
    }


    public function testImmutability(): void
    {
        $logger1 = FileLogger::create($this->tempFile);
        $otherFile = $this->tempFile . '_other';
        $logger2 = $logger1->withFilePath($otherFile);

        $this->assertNotSame($logger1, $logger2);

        $logger1->log('To original');
        $content = file_get_contents($this->tempFile);
        $this->assertStringContainsString('To original', $content);

        @unlink($otherFile);
    }
}
