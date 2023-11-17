<?php declare(strict_types= 1);

namespace thegroovetrain\PiratePHP;


trait HasAttributes {

    private $attributes = [];

    
    public function withAttribute(string $key, mixed $value):static
    {
        $new = clone $this;
        $new->attributes[$key] = $value;
        return $new;
    }


    public function withoutAttribute(string ...$keys):static
    {
        $new = clone $this;
        foreach($keys as $key) {
            unset($new->attributes[$key]);
        }
        return $new;
    }


    public function getAttribute(string $key):mixed
    {
        return $this->attributes[$key] ?? null;
    }
}