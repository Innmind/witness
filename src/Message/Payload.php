<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message;

use Innmind\Witness\{
    Actor\Address,
    Message\Payload\Serialized,
    Message\Payload\Shape,
    Message\Payload\Values,
    Message\Payload\Value,
    Mailboxes,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\PointInTime;
use Innmind\Validation\{
    Is,
    Of,
    Constraint,
};
use Innmind\Json\Json;
use Innmind\Immutable\{
    Maybe,
    Validation,
};

/**
 * @psalm-immutable
 */
final class Payload
{
    private function __construct(
        private Shape|Values $implementation,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param array<array-key, string|int|float|bool|self|Address|PointInTime|null> $shape
     */
    public static function of(array $shape): self
    {
        return new self(Shape::of($shape));
    }

    /**
     * @psalm-pure
     * @no-named-arguments
     */
    public static function values(string|int|float|bool|self|Address|PointInTime|null ...$values): self
    {
        return new self(Values::of(...$values));
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @return Maybe<self>
     */
    public static function denormalize(
        OperatingSystem $os,
        Mailboxes $mailboxes,
        Address\Name $currentActor,
        mixed $value,
    ): Maybe {
        /** @var Constraint<mixed, string|int|float|bool|self|Address|PointInTime|null> */
        $type = Of::callable(static fn(mixed $value) => Validation::success(
            Value::denormalize($os, $mailboxes, $currentActor, $value),
        ))->and(Is::just());
        $values = Is::list($type)->map(
            static fn($values) => self::values(...$values),
        );
        $shape = Is::associativeArray(
            Is::string()->or(Is::int()),
            $type,
        )
            ->map(Shape::ofMap(...))
            ->map(static fn($shape) => new self($shape));
        $validate = $shape->or($values);

        return $validate($value)->maybe();
    }

    /**
     * @psalm-pure
     * @internal
     *
     * @return Maybe<self>
     */
    public static function deserialize(
        OperatingSystem $os,
        Mailboxes $mailboxes,
        Address\Name $currentActor,
        Serialized $encoded,
    ): Maybe {
        return Maybe::just($encoded->unwrap())
            ->flatMap(Json::maybeDecode(...))
            ->flatMap(static fn(mixed $value) => self::denormalize(
                $os,
                $mailboxes,
                $currentActor,
                $value,
            ));
    }

    /**
     * @internal
     */
    public function serialize(): Serialized
    {
        return Serialized::of(Json::encode(
            $this->implementation->normalize(),
        ));
    }

    public function unwrap(): Shape|Values
    {
        return $this->implementation;
    }
}
