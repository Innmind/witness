<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
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
        /**
         * Force the type otherwise the address type should be carried by the
         * address name, which is impractical and would complexify the
         * implementation of the Mailboxes interface.
         * @var Maybe<Address<T>>
         */
        return $this
            ->mailboxes
            ->for($this->os, Name::new())
            ->flatMap(
                fn($mailbox) => $this
                    ->scheduled
                    ->push(
                        $this->os,
                        $mailbox->address()->name(),
                    )
                    ->map(static fn() => $mailbox->address()),
            )
            ->flatMap(static fn($address) => $address(Sequence::of(Init::of($actor, $argument)))->map(
                static fn() => $address,
            ));
    }

    public static function of(
        OperatingSystem $os,
        Mailboxes $mailboxes,
        Scheduled $scheduled,
        Name $spawner,
    ): self {
        return new self($os, $mailboxes, $scheduled, $spawner);
    }
}
