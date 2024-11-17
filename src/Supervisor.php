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
        private Scheduled $scheduled,
        private Mailboxes $mailboxes,
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
            // todo cleanup the scheduled actors
            return $continuation->terminate();
        }

        return $continuation->launch(
            $this
                ->scheduled
                ->pull($os)
                ->map(Process::task(
                    $this->mailboxes,
                    $this->scheduled,
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
        Scheduled $scheduled,
        Mailboxes $mailboxes,
        Factories $factories,
        Denormalize $denormalize,
        ?Period $terminationGrace,
    ): self {
        return new self(
            $scheduled,
            $mailboxes,
            $factories,
            $denormalize,
            $terminationGrace ?? Hour::of(1),
        );
    }
}
