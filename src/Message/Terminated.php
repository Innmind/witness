<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message;

use Innmind\Witness\{
    Message,
    Denormalize,
};
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Terminated implements Message
{
    private const KEY = 'innmind-witness-signal-terminated';

    private function __construct(
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function new(): self
    {
        return new self;
    }

    /**
     * @psalm-pure
     */
    public static function denormalize(
        Denormalize $denormalize,
        Payload $payload,
    ): Maybe {
        return Maybe::just($payload->unwrap())
            ->keep(Instance::of(Payload\Shape::class))
            ->map(static fn($shape) => $shape->unwrap())
            ->flatMap(static fn($shape) => $shape->get('id'))
            ->filter(static fn($id) => $id === self::KEY)
            ->map(static fn() => new self);
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => self::KEY,
        ]);
    }
}
