<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Mantle\{
    Task,
    Source\Continuation,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Source
{
    public function __construct(
        private Spawn $spawn,
        private Actors $actors,
    ) {
    }

    public function __invoke(
        null $carry,
        OperatingSystem $os,
        Continuation $continuation,
        Sequence $results,
    ): Continuation {
        // listen for new scheduled actors
        // todo terminate when no more tasks and no more scheduled actors
        $tasks = $this
            ->actors
            ->scheduled()
            ->map(static fn($actor) => Task::of(
                static fn($os) => null, // todo
            ));

        return $continuation->launch($tasks);
    }
}
