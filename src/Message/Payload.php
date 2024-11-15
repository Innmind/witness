<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message;

use Innmind\Witness\Actor\Address;

/**
 * @psalm-immutable
 */
final class Payload
{
    private function __construct()
    {
    }

    /**
     * @psalm-pure
     *
     * @param array<array-key, string|int|float|bool|self|Address|null> $shape
     */
    public static function of(array $shape): self
    {
        return new self;
    }

    /**
     * @psalm-pure
     * @no-named-arguments
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
