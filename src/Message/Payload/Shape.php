<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message\Payload;

use Innmind\Witness\{
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
     * @return Map<array-key, string|int|float|bool|Payload|Address|PointInTime|null>
     */
    public function unwrap(): Map
    {
        return $this->shape;
    }
}
