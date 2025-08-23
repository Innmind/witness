<?php
declare(strict_types = 1);

namespace Innmind\Actors\Actor\Mailbox;

use Innmind\Actors\{
    Actor\Mailbox,
    Actor\Address,
    Message\Payload\Serialized,
};
use Innmind\Immutable\{
    Maybe,
    Sequence,
    SideEffect,
};

final class Nil implements Mailbox
{
    private function __construct(
        private Address\Name $name,
    ) {
    }

    public static function of(Address\Name $name): self
    {
        return new self($name);
    }

    #[\Override]
    public function address(Address\Name $sender): Address
    {
        return Address::of($this->name, $this, $sender);
    }

    #[\Override]
    public function push(Sequence $messages): Maybe
    {
        // Act as if the messages were sent so the calling actor can't know the
        // actor no longer exist.
        return Maybe::just(new SideEffect);
    }

    #[\Override]
    public function pull(): Maybe
    {
        // This method should never be called by Process since a Nil mailbox
        // should only be built when decoding a message where the address no
        // longer exist.
        /** @var Maybe<Serialized> */
        return Maybe::nothing();
    }
}
