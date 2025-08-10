<?php
declare(strict_types = 1);

namespace Innmind\Actors\Message\Payload;

use Innmind\Actors\{
    Actor\Address,
    Message\Payload,
};
use Innmind\TimeContinuum\PointInTime;
use Innmind\Immutable\Map;

/**
 * @psalm-immutable
 */
final class Shape
{
    /**
     * @param Map<array-key, string|int|float|bool|Payload|Address|PointInTime|null> $shape
     */
    private function __construct(
        private Map $shape,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param array<array-key, string|int|float|bool|Payload|Address|PointInTime|null> $shape
     */
    public static function of(array $shape): self
    {
        /** @var Map<array-key, string|int|float|bool|Payload|Address|PointInTime|null> */
        $map = Map::of();

        foreach ($shape as $key => $value) {
            $map = ($map)($key, $value);
        }

        return new self($map);
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @param Map<array-key, string|int|float|bool|Payload|Address|PointInTime|null> $shape
     */
    public static function ofMap(Map $shape): self
    {
        return new self($shape);
    }

    /**
     * @return Map<array-key, string|int|float|bool|Payload|Address|PointInTime|null>
     */
    public function unwrap(): Map
    {
        return $this->shape;
    }

    /**
     * @internal
     */
    public function normalize(): array
    {
        $pairs = $this
            ->shape
            ->map(static fn($_, $value) => Value::normalize($value))
            ->toSequence()
            ->toList();
        $raw = [];

        foreach ($pairs as $pair) {
            $raw[$pair->key()] = $pair->value();
        }

        return $raw;
    }
}
