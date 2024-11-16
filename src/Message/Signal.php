<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message;

use Innmind\Witness\{
    Actor\Address,
    Message,
    Signal\ChildFailed,
    Signal\Terminated,
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
final class Signal implements Message
{
    private const KEY = 'innmind-witness-signal';

    private function __construct(
        private string $kind,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function terminated(): self
    {
        return new self('terminated');
    }

    /**
     * @psalm-pure
     */
    public static function childFailed(): self
    {
        return new self('child-failed');
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
            ->flatMap(
                static fn($shape) => Maybe::all(
                    $shape
                        ->get('id')
                        ->filter(static fn($id) => $id === self::KEY),
                    $shape
                        ->get('kind')
                        ->filter(static fn($kind) => $kind === 'terminated' || $kind === 'child-failed'),
                )->map(static fn($_, string $kind) => new self($kind)),
            );
    }

    public function signal(Address $child): Terminated|ChildFailed
    {
        return match ($this->kind) {
            'terminated' => Terminated::of($child),
            'child-failed' => ChildFailed::of($child),
        };
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => self::KEY,
            'kind' => $this->kind,
        ]);
    }
}
