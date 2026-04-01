<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


interface RendererInterface
{
    public function render(string $template, array $data = []): string;
}
