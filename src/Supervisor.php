<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Mantle\Source\Continuation;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
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

    /**
     * @param Continuation<SideEffect, mixed> $continuation
     * @param Sequence<mixed> $results
     *
     * @return Continuation<SideEffect, mixed>
     */
    public function __invoke(
        SideEffect $carry,
        OperatingSystem $os,
        Continuation $continuation,
        Sequence $results,
    ): Continuation {
        // todo terminate when no more actors running
        // could this be signaled via the root actor tasks returning some
        // special object ?
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
