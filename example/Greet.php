<?php
declare(strict_types = 1);

namespace Example;

use Innmind\Witness\Message;
use Innmind\Immutable\Maybe;

final class Greet implements Message
{
    private ?string $name;

    private function __construct(?string $name)
    {
        $this->name = $name;
    }

    public static function newcomer(string $name): self
    {
        return new self($name);
    }

    public static function all(): self
    {
        return new self(null);
    }

    public function get(string $key): Maybe
    {
        return match($key) {
            'name' => Maybe::of($this->name),
        };
    }
}
