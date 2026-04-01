<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP\Tests;

use PHPUnit\Framework\TestCase;
use thegroovetrain\PiratePHP\PhpRenderer;
use thegroovetrain\PiratePHP\TemplateNotFoundException;

class PhpRendererTest extends TestCase
{
    private string $tempDir;


    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/piratephp_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }


    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }


    public function testRenderTemplateWithVariables(): void
    {
        file_put_contents($this->tempDir . '/hello.php', 'Hello, <?= $name ?>!');

        $renderer = PhpRenderer::create($this->tempDir);
        $result = $renderer->render('hello.php', ['name' => 'Pirate']);

        $this->assertSame('Hello, Pirate!', $result);
    }


    public function testRenderTemplateWithMultipleVariables(): void
    {
        file_put_contents($this->tempDir . '/page.php', '<h1><?= $title ?></h1><p><?= $body ?></p>');

        $renderer = PhpRenderer::create($this->tempDir);
        $result = $renderer->render('page.php', ['title' => 'Ahoy', 'body' => 'Welcome aboard']);

        $this->assertSame('<h1>Ahoy</h1><p>Welcome aboard</p>', $result);
    }


    public function testMissingTemplateThrowsException(): void
    {
        $renderer = PhpRenderer::create($this->tempDir);

        $this->expectException(TemplateNotFoundException::class);
        $this->expectExceptionMessage('Template not found: nonexistent.php');

        $renderer->render('nonexistent.php');
    }


    public function testRenderTemplateWithEmptyData(): void
    {
        file_put_contents($this->tempDir . '/static.php', '<p>No variables here</p>');

        $renderer = PhpRenderer::create($this->tempDir);
        $result = $renderer->render('static.php');

        $this->assertSame('<p>No variables here</p>', $result);
    }


    public function testRenderTemplateWithEmptyDataArray(): void
    {
        file_put_contents($this->tempDir . '/static.php', '<p>Static content</p>');

        $renderer = PhpRenderer::create($this->tempDir);
        $result = $renderer->render('static.php', []);

        $this->assertSame('<p>Static content</p>', $result);
    }


    public function testOutputBufferingCleanupOnError(): void
    {
        file_put_contents($this->tempDir . '/error.php', '<?php throw new \RuntimeException("template error"); ?>');

        $renderer = PhpRenderer::create($this->tempDir);
        $levelBefore = ob_get_level();

        try {
            $renderer->render('error.php');
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('template error', $e->getMessage());
            $this->assertSame($levelBefore, ob_get_level(), 'Output buffer level should be restored after error');
        }
    }


    public function testCreateReturnsInstance(): void
    {
        $renderer = PhpRenderer::create($this->tempDir);

        $this->assertInstanceOf(PhpRenderer::class, $renderer);
        $this->assertSame($this->tempDir, $renderer->getBasePath());
    }


    public function testWithBasePathReturnsNewInstance(): void
    {
        $renderer = PhpRenderer::create($this->tempDir);
        $newRenderer = $renderer->withBasePath('/other/path');

        $this->assertNotSame($renderer, $newRenderer);
        $this->assertSame($this->tempDir, $renderer->getBasePath());
        $this->assertSame('/other/path', $newRenderer->getBasePath());
    }


    public function testExtractSkipDoesNotOverwriteInternalVars(): void
    {
        // EXTR_SKIP prevents data keys from overwriting internal render() variables
        // $file is used internally by render(), so passing it as data should not break things
        file_put_contents($this->tempDir . '/skip.php', 'Path: <?= $file ?>');

        $renderer = PhpRenderer::create($this->tempDir);
        $result = $renderer->render('skip.php', ['file' => 'should-be-ignored']);

        // $file should be the internal path, not the user-supplied value
        $this->assertStringContainsString($this->tempDir, $result);
    }
}
