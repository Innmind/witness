<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message\Payload;

use Innmind\Witness\{
    Actor\Address,
    Message\Payload,
};
use Innmind\TimeContinuum\PointInTime;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Values
{
    /**
     * @param Sequence<string|int|float|bool|Payload|Address|PointInTime|null> $values
     */
    private function __construct(
        private Sequence $values,
    ) {
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    public static function of(string|int|float|bool|Payload|Address|PointInTime|null ...$values): self
    {
        return new self(Sequence::of(...$values));
    }

    /**
     * @return Sequence<string|int|float|bool|Payload|Address|PointInTime|null>
     */
    public function unwrap(): Sequence
    {
        return $this->values;
    }

    /**
     * @internal
     */
    public function normalize(): array
    {
        return $this
            ->values
            ->map(Value::normalize(...))
            ->toList();
    }
}
