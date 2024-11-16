<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Actor\Address\Name,
    Message\Init,
    Message\Tell,
    Message\Payload,
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
        private Denormalize $denormalize,
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

        $actor = $mailbox
            ->pull()
            ->flatMap(fn($serialized) => Payload::deserialize(
                $os,
                $this->mailboxes,
                $this->name,
                $serialized,
            ))
            ->flatMap($this->denormalize)
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
                    ->pull()
                    ->flatMap(fn($serialized) => Payload::deserialize(
                        $os,
                        $this->mailboxes,
                        $this->name,
                        $serialized,
                    ))
                    ->flatMap($this->denormalize)
                    ->keep(Instance::of(Tell::class))
                    ->flatMap(
                        fn($tell) => $this
                            ->mailboxes
                            ->for($os, $tell->sender())
                            ->map(fn($mailbox) => Receive::message(
                                $tell->message(),
                                $mailbox->address($this->name),
                            )),
                    )
                    ->match(
                        static fn($receive) => $receive,
                        static fn() => null,
                    );

                if (\is_null($receive)) {
                    // We silently ignore messages that failed to be retieved or
                    // deserialized to let the system continue processing.
                    // The alternative would be to crash the process, notify the
                    // supervisor or parent actor to know what to do next. But
                    // this is too much complexity to implement (at least for
                    // now).
                    // This allows the overhaul system to be more efficient.
                    continue;
                }

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
        Denormalize $denormalize,
    ): callable {
        return static fn(Name $address) => Task::of(
            new self($mailboxes, $scheduled, $factories, $denormalize, $address),
        );
    }
}
