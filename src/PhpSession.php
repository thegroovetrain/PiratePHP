<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class PhpSession implements SessionInterface
{
    private array $data;


    public function __construct(array $data = [])
    {
        $this->data = $data;
    }


    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }


    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }


    public function all(): array
    {
        return $this->data;
    }
}
