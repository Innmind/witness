<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Spawn\Children,
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
        private Adapter $adapter,
        private Children $children,
        private Name $spawner,
    ) {
    }

    /**
     * @template I of Message
     * @template A of ?Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $actor
     * @param A $argument
     *
     * @return Maybe<Address<T>>
     */
    public function __invoke(string $actor, ?Message $argument = null): Maybe
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
            ->adapter
            ->mailboxes()
            ->generate($this->os)
            ->flatMap(
                static fn($mailbox) => $mailbox
                    ->push(Sequence::of($message))
                    ->map(static fn() => $mailbox),
            )
            ->flatMap(
                fn($mailbox) => $this
                    ->adapter
                    ->scheduled()
                    ->push(
                        $this->os,
                        $mailbox->address($this->spawner)->name(),
                    )
                    ->map(fn() => $mailbox->address($this->spawner))
                    ->map($this->children->add(...)),
            );
    }

    /**
     * @internal
     */
    public static function of(
        OperatingSystem $os,
        Adapter $adapter,
        Children $children,
        Name $spawner,
    ): self {
        return new self($os, $adapter, $children, $spawner);
    }
}
