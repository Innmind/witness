<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Actor\Address\Name,
    Message\Init,
    Message\Tell,
    Message\Payload,
    Signal\PostStop,
    Signal\Restart,
    Signal\Terminated,
    Receive\Continuation,
};
use Innmind\Mantle\Task;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    Sequence,
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

        $spawn = Spawn::of(
            $os,
            $this->mailboxes,
            $this->scheduled,
            $counter = Spawn\Counter::start(),
            $this->name,
        );

        [$parent, $actor] = $mailbox
            ->pull()
            ->flatMap(fn($serialized) => Payload::deserialize(
                $os,
                $this->mailboxes,
                $this->name,
                $serialized,
            ))
            ->flatMap($this->denormalize)
            ->keep(Instance::of(Init::class))
            ->flatMap(
                fn($init) => ($this->factories)(
                    $init->actor(),
                    $init->argument(),
                    $spawn,
                )->map(fn($actor) => [
                    $init
                        ->parent()
                        ->flatMap(fn($parent) => $this->mailboxes->for(
                            $os,
                            $parent,
                        ))
                        ->map(fn($mailbox) => $mailbox->address($this->name))
                        ->match(
                            static fn($parent) => $parent,
                            static fn() => null,
                        ),
                    $actor,
                ]),
            )
            ->match(
                static fn($init) => $init,
                static fn() => [null, null],
            );

        if (\is_null($parent) && !$this->name->equals(Name::root())) {
            // This case should not exist as only the root actor should not have
            // a parent actor. But if this case arise this means that the parent
            // may no longer exist.
            // A child can't live without its parent, so we prevent the child
            // from running.
            // By deleting its mailbox we make sure the actor can never be run
            // and no new messages can accumulated for it.
            $this->mailboxes->delete($this->name)->match(
                static fn() => null,
                static fn() => null,
            );

            return;
        }

        if (\is_null($actor)) {
            // todo send parent failed ?
            return;
        }

        $receive = null;
        $continue = true;

        do {
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
                    fn($tell) => match (true) {
                        $tell->message() instanceof Message\Terminated => Maybe::just(
                            Receive::signal(Terminated::of($tell->sender())),
                        )->map($counter->decrement(...)),
                        default => $this
                            ->mailboxes
                            ->for($os, $tell->sender())
                            ->map(fn($mailbox) => Receive::message(
                                $tell->message(),
                                $mailbox->address($this->name),
                            )),
                    },
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

            try {
                $continue = $actor($receive)
                    ->handle(Continuation::new())
                    ->match(
                        static fn() => true,
                        static fn() => false,
                    );
            } catch (\Throwable $e) {
                // todo Should be stop the whole system if the root actor crashes ?
                if (!\is_null($parent)) {
                    $message = Message\ChildFailed::of($e);
                    // If the signal is not sent it should mean the parent no
                    // longer exist. And like the comment at the top of this
                    // method explains, the child can't live without its parent
                    // so we stop this child.
                    $continue = $parent(Sequence::of($message))->match(
                        static fn() => true,
                        static fn() => false,
                    );

                    if (!$continue) {
                        continue;
                    }
                }

                $receive = Receive::signal(Restart::of($e));
                $continue = true;
            }
        } while ($continue);

        $receiveTerminations = true;

        // todo force stopping after a grace period in case we never receive
        // enough Terminated signals. This case could happen in case of network
        // errors.
        while (!$counter->childless()) {
            $receive = $mailbox
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
                    static fn($tell) => Maybe::just($tell->message())
                        ->keep(Instance::of(Message\Terminated::class))
                        ->map(static fn($message) => Receive::signal(
                            Terminated::of($tell->sender()),
                        )),
                )
                ->match(
                    static fn($receive) => $receive,
                    static fn() => null,
                );

            if (\is_null($receive)) {
                // Silently discarded any messages received. Only Terminated
                // signals are allowed when stopping an actor.
                continue;
            }

            $counter->decrement(null);

            if (!$receiveTerminations) {
                // If the actor asked to not continue when receiving a
                // Terminated signal while stopping we continue to wait for all
                // children to stop. We simply no longer make the actor aware
                // of the following signals.
                continue;
            }

            try {
                $receiveTerminations = $actor($receive)
                    ->handle(Continuation::new())
                    ->match(
                        static fn() => true,
                        static fn() => false,
                    );
            } catch (\Throwable $e) {
                // We don't allow the actor to recover from failures.
            }
        }

        try {
            // In any case the actor can't restart when stopping.
            $actor(Receive::signal(new PostStop))
                ->handle(Continuation::new())
                ->match(
                    static fn() => null,
                    static fn() => null,
                );
        } catch (\Throwable $e) {
            // Do not notify the parent the child has failed because the mailbox
            // may be already deleted by the time it gets the signal. And in any
            // case it couldn't recover the actor since it will be terminated.
            // And the parent will get the Terminated signal anyway.
            // If someone needs to debug any error occuring during the PostStop
            // signal then it should catch the error inside the
            // Receive::onPostStop() callable.
        }

        $this->mailboxes->delete($this->name)->match(
            static fn() => null, // deleted
            static fn() => null, // todo what to do in this case ?
        );

        if (\is_null($parent)) {
            // This means this is the root actor. We should return a value to
            // tell the Supervisor it should terminate itself
            // todo
            return;
        }

        $message = Message\Terminated::new();
        // If the signal is not sent it should mean the parent no longer exist.
        // At this point there's nothing we can do.
        $parent(Sequence::of($message))->match(
            static fn() => null,
            static fn() => null,
        );
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
