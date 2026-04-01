<?php declare(strict_types=1);

namespace thegroovetrain\PiratePHP;


class TemplateNotFoundException extends \RuntimeException
{
    public function __construct(string $template)
    {
        parent::__construct("Template not found: $template");
    }
}
