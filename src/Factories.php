<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Immutable\{
    Maybe,
    Map,
};

/**
 * @internal
 */
final class Factories
{
    /**
     * @param Map<class-string<Actor>, callable(Message, Spawn): Actor> $factories
     */
    private function __construct(
        private Map $factories,
    ) {
    }

    /**
     * @template M of Message
     * @template A of Message
     * @template T of Actor<M, A>
     *
     * @param class-string<T> $actor
     * @param A $argument
     *
     * @return Maybe<T>
     */
    public function __invoke(
        string $actor,
        Message $argument,
        Spawn $spawn,
    ): Maybe {
        /** @var Maybe<T> */
        return $this
            ->factories
            ->get($actor)
            ->map(static fn($factory) => $factory(
                $argument,
                $spawn,
            ));
    }

    /**
     * @param Map<class-string<Actor>, callable(Message, Spawn): Actor> $factories
     */
    public static function of(Map $factories): self
    {
        return new self($factories);
    }
}
