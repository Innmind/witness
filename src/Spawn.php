<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Spawn\Counter,
    Actor\Address,
    Actor\Address\Name,
    Message\Init,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    Sequence,
};

final class Spawn
{
    private function __construct(
        private OperatingSystem $os,
        private Mailboxes $mailboxes,
        private Scheduled $scheduled,
        private Counter $counter,
        private Name $spawner,
    ) {
    }

    /**
     * @template I of Message
     * @template A of Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $actor
     * @param A $argument
     *
     * @return Maybe<Address<T>>
     */
    public function __invoke(string $actor, Message $argument): Maybe
    {
        $message = Init::of($this->spawner, $actor, $argument)
            ->normalize()
            ->serialize();

        /**
         * Force the type otherwise the address type should be carried by the
         * address name, which is impractical and would complexify the
         * implementation of the Mailboxes interface.
         * @var Maybe<Address<T>>
         */
        return $this
            ->mailboxes
            ->generate($this->os)
            ->flatMap(
                fn($mailbox) => $this
                    ->scheduled
                    ->push(
                        $this->os,
                        $mailbox->address($this->spawner)->name(),
                    )
                    ->flatMap(static fn() => $mailbox->push(Sequence::of($message)))
                    ->map(fn() => $this->counter->increment())
                    ->map(fn() => $mailbox->address($this->spawner)),
            );
    }

    /**
     * @internal
     */
    public static function of(
        OperatingSystem $os,
        Mailboxes $mailboxes,
        Scheduled $scheduled,
        Counter $counter,
        Name $spawner,
    ): self {
        return new self($os, $mailboxes, $scheduled, $counter, $spawner);
    }
}
