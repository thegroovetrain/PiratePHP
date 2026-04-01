<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class CookieJar implements CookieInterface
{
    private array $cookies;


    private function __construct(array $cookies = [])
    {
        $this->cookies = $cookies;
    }


    public static function create(): static
    {
        return new static();
    }


    public static function fromHeaderString(string $cookieHeader): static
    {
        $cookies = [];
        $pairs = explode(';', $cookieHeader);
        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                continue;
            }
            $eqPos = strpos($pair, '=');
            if ($eqPos === false) {
                continue;
            }
            $name = trim(substr($pair, 0, $eqPos));
            $value = trim(substr($pair, $eqPos + 1));
            if ($name !== '') {
                $cookies[$name] = urldecode($value);
            }
        }
        return new static($cookies);
    }


    public function get(string $name, ?string $default = null): ?string
    {
        return $this->cookies[$name] ?? $default;
    }


    public function has(string $name): bool
    {
        return array_key_exists($name, $this->cookies);
    }


    public function all(): array
    {
        return $this->cookies;
    }
}
