<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


interface SessionInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    public function all(): array;

    public function with(string $key, mixed $value): static;

    public function without(string $key): static;
}
