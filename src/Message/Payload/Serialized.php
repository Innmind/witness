<?php
declare(strict_types = 1);

namespace Innmind\Actors\Message\Payload;

/**
 * @psalm-immutable
 */
final class Serialized
{
    private function __construct(
        private string $value,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(string $value): self
    {
        return new self($value);
    }

    public function unwrap(): string
    {
        return $this->value;
    }
}
