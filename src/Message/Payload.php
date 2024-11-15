<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message;

use Innmind\Witness\{
    Actor,
    Actor\Address,
    Message,
};

final class Payload
{
    private function __construct()
    {
    }

    /**
     * @param array<array-key, string|int|float|bool|self|Address<Message, Actor>|null> $shape
     */
    public static function of(array $shape): self
    {
        return new self;
    }

    /**
     * @param string|int|float|bool|self|Address<Message, Actor>|null $values
     */
    public static function values(string|int|float|bool|self|Address|null ...$values): self
    {
        return new self;
    }

    public function unwrap(): array
    {
        return [];
    }
}
