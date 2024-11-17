<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Supervisor\DeadRootActor;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Mantle\Source\Continuation;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
    Predicate\Instance,
};

final class Supervisor
{
    public function __construct(
        private Scheduled $scheduled,
        private Mailboxes $mailboxes,
        private Factories $factories,
        private Denormalize $denormalize,
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
                ))
                ->toSequence(),
        );
    }

    public static function of(
        Scheduled $scheduled,
        Mailboxes $mailboxes,
        Factories $factories,
        Denormalize $denormalize,
    ): self {
        return new self($scheduled, $mailboxes, $factories, $denormalize);
    }
}
