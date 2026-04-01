<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class PhpRenderer implements RendererInterface
{
    private string $basePath;


    private function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }


    public static function create(string $basePath): static
    {
        return new static($basePath);
    }


    public function withBasePath(string $basePath): static
    {
        $new = clone $this;
        $new->basePath = $basePath;
        return $new;
    }


    public function getBasePath(): string
    {
        return $this->basePath;
    }


    public function render(string $template, array $data = []): string
    {
        $file = $this->basePath . '/' . $template;
        $realBase = realpath($this->basePath);
        $realFile = realpath($file);

        if ($realBase === false || $realFile === false || !str_starts_with($realFile, $realBase)) {
            throw new TemplateNotFoundException($template);
        }

        if (!is_file($realFile)) {
            throw new TemplateNotFoundException($template);
        }

        $file = $realFile;

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return ob_get_clean();
    }
}
