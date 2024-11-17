<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Supervisor\DeadRootActor;
use Innmind\Mantle\Source\Continuation;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\{
    Period,
    Earth\Period\Hour,
};
use Innmind\Immutable\{
    Sequence,
    SideEffect,
    Predicate\Instance,
};

/**
 * @internal
 */
final class Supervisor
{
    public function __construct(
        private Adapter $adapter,
        private Factories $factories,
        private Denormalize $denormalize,
        private Period $terminationGrace,
    ) {
    }

    public function __invoke(
        SideEffect $carry,
        OperatingSystem $os,
        Continuation $continuation,
        Sequence $results,
    ): Continuation {
        if ($results->any(Instance::of(DeadRootActor::class))) {
            $this->adapter->terminate($os)->match(
                static fn() => null,
                static fn() => null, // todo what to do in this case ?
            );

            return $continuation->terminate();
        }

        return $continuation->launch(
            $this
                ->adapter
                ->scheduled()
                ->pull($os)
                ->map(Process::task(
                    $this->adapter,
                    $this->factories,
                    $this->denormalize,
                    $this->terminationGrace,
                ))
                ->toSequence(),
        );
    }

    /**
     * @internal
     */
    public static function of(
        Adapter $adapter,
        Factories $factories,
        Denormalize $denormalize,
        ?Period $terminationGrace,
    ): self {
        return new self(
            $adapter,
            $factories,
            $denormalize,
            $terminationGrace ?? Hour::of(1),
        );
    }
}
