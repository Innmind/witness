<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Actor\Address\Name,
    Message\Init,
    Message\Tell,
    Signal\PostStop,
    Signal\PreRestart,
    Receive\Continuation,
};
use Innmind\Mantle\Task;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Predicate\Instance,
};

final class Process
{
    private function __construct(
        private Mailboxes $mailboxes,
        private Scheduled $scheduled,
        private Factories $factories,
        private Name $name,
    ) {
    }

    public function __invoke(OperatingSystem $os): void
    {
        $mailbox = $this
            ->mailboxes
            ->for($os, $this->name)
            ->match(
                static fn($mailbox) => $mailbox,
                static fn() => null,
            );

        if (\is_null($mailbox)) {
            return;
        }

        /** @var ?Actor */
        $actor = $mailbox
            ->pull($os)
            ->keep(Instance::of(Init::class))
            ->flatMap(fn($init) => ($this->factories)(
                $init->actor(),
                $init->argument(),
                Spawn::of(
                    $os,
                    $this->mailboxes,
                    $this->scheduled,
                    $this->name,
                ),
            ))
            ->match(
                static fn($actor) => $actor,
                static fn() => null,
            );

        if (\is_null($actor)) {
            // todo send parent failed ?
            return;
        }

        $receive = null;
        $continue = true;

        do {
            try {
                // todo handle pulling signals from children
                $receive ??= $mailbox
                    ->pull($os)
                    ->keep(Instance::of(Tell::class))
                    ->flatMap(
                        fn($tell) => $this
                            ->mailboxes
                            ->for($os, $tell->sender())
                            ->map(static fn($mailbox) => Receive::message(
                                $tell->message(),
                                $mailbox->address(),
                            )),
                    )
                    ->match(
                        static fn($receive) => $receive,
                        static fn() => throw new \RuntimeException('Failed to pull a message'),
                    );

                [$receive, $continue] = $actor($receive)
                    ->handle(Continuation::new())
                    ->match(
                        static fn() => [null, true],
                        static fn() => [
                            match ($continue) {
                                true => Receive::signal(new PostStop),
                                false => null, // means previous call already asked to stop, returning null to avoid infinite loop
                            },
                            false,
                        ],
                    );
            } catch (\RuntimeException $e) {
                // todo use a dedicated class for failing to pull message
                // todo ?
                return;
            } catch (\Throwable $e) {
                // send parent a ChildFailed ?
                $receive = Receive::signal(new PreRestart);
                $continue = true;
            }
        } while ($continue || !\is_null($receive));
    }

    /**
     * @return callable(Name): Task
     */
    public static function task(
        Mailboxes $mailboxes,
        Scheduled $scheduled,
        Factories $factories,
    ): callable {
        return static fn(Name $address) => Task::of(
            new self($mailboxes, $scheduled, $factories, $address),
        );
    }
}
