<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class FileLogger implements LoggerInterface
{
    private string $filePath;


    private function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }


    public static function create(string $filePath): static
    {
        return new static($filePath);
    }


    public function withFilePath(string $filePath): static
    {
        $new = clone $this;
        $new->filePath = $filePath;
        return $new;
    }


    public function log(string $message): void
    {
        try {
            $message = str_replace(["\n", "\r"], ' ', $message);
            @file_put_contents($this->filePath, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // silently swallow all failures
        }
    }
}
