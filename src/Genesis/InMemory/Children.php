<?php
declare(strict_types = 1);

namespace Innmind\Witness\Genesis\InMemory;

use Innmind\Witness\{
    Actor\Mailbox,
    Message,
};
use Innmind\Immutable\Set;

final class Children
{
    /** @var Set<Mailbox> */
    private Set $mailboxes;

    public function __construct()
    {
        $this->mailboxes = Set::of(Mailbox::class);
    }

    public function register(Mailbox $mailbox): void
    {
        $this->mailboxes = ($this->mailboxes)($mailbox);
    }

    public function unregister(Mailbox\Address $address): void
    {
        $this->mailboxes = $this->mailboxes->filter(
            static fn($mailbox) => $mailbox->address() !== $address,
        );
    }

    public function stop(): void
    {
        $this->mailboxes->foreach(static fn($mailbox) => $mailbox->stop());
    }

    public function empty(): bool
    {
        return $this->mailboxes->empty();
    }

    /**
     * @return Set<Mailbox\Address<Message>>
     */
    public function addresses(): Set
    {
        return $this->mailboxes->mapTo(
            Mailbox\Address::class,
            static fn($mailbox) => $mailbox->address(),
        );
    }
}
