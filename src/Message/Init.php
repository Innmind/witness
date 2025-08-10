<?php
declare(strict_types = 1);

namespace Innmind\Actors\Message;

use Innmind\Actors\{
    Message,
    Denormalize,
    Actor,
    Actor\Address\Name,
};
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};
use Ramsey\Uuid\Uuid;

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
        private ?Name $parent,
        private string $actor,
        private ?Message $argument,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param class-string<Actor> $actor
     */
    public static function root(string $actor, ?Message $argument): self
    {
        return new self(null, $actor, $argument);
    }

    /**
     * @psalm-pure
     *
     * @param class-string<Actor> $actor
     */
    public static function of(Name $parent, string $actor, ?Message $argument): self
    {
        return new self($parent, $actor, $argument);
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
                        ->get('parent')
                        ->keep(
                            Is::string()
                                ->or(Is::null())
                                ->asPredicate(),
                        )
                        ->filter(static fn($value) => \is_null($value) || Uuid::isValid($value) || $value === 'root')
                        ->map(static fn($value) => match ($value) {
                            null => null,
                            'root' => Name::root(),
                            default => Name::of(Uuid::fromString($value)),
                        }),
                    $shape
                        ->get('actor')
                        ->keep(Is::string()->asPredicate()),
                    $shape
                        ->get('argument')
                        ->keep(Instance::of(Payload::class)->or(
                            Is::null()->asPredicate(),
                        ))
                        ->flatMap(static fn($argument) => match ($argument) {
                            null => Maybe::just(null),
                            default => $denormalize($argument)
                        }),
                )->map(
                    /** @psalm-suppress ArgumentTypeCoercion Due to the actor string not being a class-string */
                    static fn(string $_, ?Name $parent, string $actor, ?Message $argument) => new self(
                        $parent,
                        $actor,
                        $argument,
                    ),
                ),
            );
    }

    /**
     * @return Maybe<Name>
     */
    public function parent(): Maybe
    {
        return Maybe::of($this->parent);
    }

    /**
     * @return class-string<Actor>
     */
    public function actor(): string
    {
        return $this->actor;
    }

    public function argument(): ?Message
    {
        return $this->argument;
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => self::KEY,
            'parent' => $this->parent?->toString(),
            'actor' => $this->actor,
            'argument' => $this->argument?->normalize(),
        ]);
    }
}
