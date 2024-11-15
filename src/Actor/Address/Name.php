<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Address;

use Ramsey\Uuid\{
    UuidInterface,
    Uuid,
};

/**
 * @psalm-immutable
 */
final class Name
{
    /**
     * @param non-empty-string $value
     */
    private function __construct(
        private string $value,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function root(): self
    {
        return new self('root');
    }

    public static function new(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public function of(UuidInterface $uuid): self
    {
        return new self($uuid->toString());
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->value;
    }
}
