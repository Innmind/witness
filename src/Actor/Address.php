<?php
declare(strict_types = 1);

namespace Innmind\Actors\Actor;

use Innmind\Actors\{
    Actor,
    Actor\Address\Name,
    Message,
    Message\Tell,
};
use Innmind\Immutable\{
    Maybe,
    Sequence,
    SideEffect,
};

/**
 * @template T of Actor
 */
final class Address
{
    private function __construct(
        private Name $name,
        private Mailbox $mailbox,
        private Name $sender,
    ) {
    }

    /**
     * @template M of Message
     * @template I of T<M>
     *
     * @param Sequence<M> $messages
     *
     * @return Maybe<SideEffect>
     */
    public function __invoke(Sequence $messages): Maybe
    {
        return $this->mailbox->push(
            $messages
                ->map(fn($message) => Tell::of($this->sender, $message))
                ->map(static fn($message) => $message->normalize()->serialize()),
        );
    }

    public static function of(
        Name $name,
        Mailbox $mailbox,
        Name $sender,
    ): self {
        return new self(
            $name,
            $mailbox,
            $sender,
        );
    }

    public function name(): Name
    {
        return $this->name;
    }
}
