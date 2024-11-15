<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor;

use Innmind\Witness\{
    Actor,
    Actor\Address\Name,
    Message,
};
use Innmind\OperatingSystem\OperatingSystem;
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
        private OperatingSystem $os,
        private Mailbox $mailbox,
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
        return $this->mailbox->push($this->os, $messages);
    }

    public static function of(
        Name $name,
        OperatingSystem $os,
        Mailbox $mailbox,
    ): self {
        return new self(
            $name,
            $os,
            $mailbox,
        );
    }

    public function name(): Name
    {
        return $this->name;
    }
}
