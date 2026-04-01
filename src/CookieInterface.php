<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


interface CookieInterface
{
    public function get(string $name, ?string $default = null): ?string;

    public function has(string $name): bool;

    public function all(): array;
}
