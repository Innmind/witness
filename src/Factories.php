<?php
declare(strict_types = 1);

namespace Innmind\Actors;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    Map,
    Predicate\Instance,
};

/**
 * @internal
 */
final class Factories
{
    /**
     * @param Map<class-string<Actor>, callable(OperatingSystem, ?Message, Spawn): Actor> $factories
     */
    private function __construct(
        private Map $factories,
    ) {
    }

    /**
     * @template M of Message
     * @template A of ?Message
     * @template T of Actor<M, A>
     *
     * @param class-string<T> $actor
     * @param A $argument
     *
     * @return Maybe<T>
     */
    public function __invoke(
        OperatingSystem $os,
        string $actor,
        ?Message $argument,
        Spawn $spawn,
    ): Maybe {
        /** @var Maybe<T> */
        return $this
            ->factories
            ->get($actor)
            ->map(static function($factory) use ($os, $argument, $spawn) {
                try {
                    return $factory($os, $argument, $spawn);
                } catch (\Throwable $e) {
                    return;
                }
            })
            ->keep(Instance::of($actor));
    }

    /**
     * @param Map<class-string<Actor>, callable(OperatingSystem, ?Message, Spawn): Actor> $factories
     */
    public static function of(Map $factories): self
    {
        return new self($factories);
    }
}
