<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message;

use Innmind\Witness\{
    Message,
    Denormalize,
    Actor,
};
use Innmind\TimeContinuum\Clock;
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Init implements Message
{
    private const KEY = 'innmind-witness-actor-init';

    /**
     * @param class-string<Actor> $actor
     */
    private function __construct(
        private string $actor,
        private Message $argument,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param class-string<Actor> $actor
     */
    public static function of(string $actor, Message $argument): self
    {
        return new self($actor, $argument);
    }

    /**
     * @psalm-pure
     */
    public static function denormalize(
        Denormalize $denormalize,
        Clock $clock,
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
                        ->get('actor')
                        ->keep(Is::string()->asPredicate()),
                    $shape
                        ->get('argument')
                        ->keep(Instance::of(Payload::class))
                        ->flatMap(static fn($payload) => $denormalize(
                            $clock,
                            $payload,
                        )),
                )->map(
                    /** @psalm-suppress ArgumentTypeCoercion Due to the actor string not being a class-string */
                    static fn(string $_, string $actor, Message $argument) => new self(
                        $actor,
                        $argument,
                    ),
                ),
            );
    }

    /**
     * @return class-string<Actor>
     */
    public function actor(): string
    {
        return $this->actor;
    }

    public function argument(): Message
    {
        return $this->argument;
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => self::KEY,
            'actor' => $this->actor,
            'argument' => $this->argument->normalize(),
        ]);
    }
}
