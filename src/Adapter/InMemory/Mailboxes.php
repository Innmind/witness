<?php
declare(strict_types = 1);

namespace Innmind\Actors\Adapter\InMemory;

use Innmind\Actors\{
    Adapter\Mailboxes as MailboxesInterface,
    Actor\Address,
    Actor\Address\Name,
    Actor\Mailbox\Nil,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Map,
    Maybe,
    SideEffect,
};

final class Mailboxes implements MailboxesInterface
{
    /**
     * @param Map<non-empty-string, Mailbox> $mailboxes
     */
    private function __construct(
        private Map $mailboxes,
        private bool $valid,
    ) {
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self(Map::of(), true);
    }

    #[\Override]
    public function root(OperatingSystem $os): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<Mailbox> */
            return Maybe::nothing();
        }

        $name = Name::root();

        return Mailbox::of($os, $name)->map(function($mailbox) use ($name) {
            $this->mailboxes = ($this->mailboxes)(
                $name->toString(),
                $mailbox,
            );

            return $mailbox;
        });
    }

    #[\Override]
    public function generate(OperatingSystem $os): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<Mailbox> */
            return Maybe::nothing();
        }

        $name = Name::new();

        return Mailbox::of($os, $name)->map(function($mailbox) use ($name) {
            $this->mailboxes = ($this->mailboxes)(
                $name->toString(),
                $mailbox,
            );

            return $mailbox;
        });
    }

    #[\Override]
    public function for(OperatingSystem $os, Name $name): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<Mailbox> */
            return Maybe::nothing();
        }

        // We swap the OS instance to make sure we always use the expected
        // context (sync vs async). The root actor changes it OS instance
        // between the initial spawn and when it's processed.
        return $this
            ->mailboxes
            ->get($name->toString())
            ->map(static fn($mailbox) => $mailbox->swap($os));
    }

    #[\Override]
    public function address(
        OperatingSystem $os,
        Name $name,
        Name $currentActor,
    ): Address {
        return $this
            ->for($os, $name)
            ->map(static fn($mailbox) => $mailbox->address($currentActor))
            ->match(
                static fn($address) => $address,
                static fn() => Nil::of($name)->address($currentActor),
            );
    }

    #[\Override]
    public function delete(Name $name): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        $deleted = $this
            ->mailboxes
            ->get($name->toString())
            ->map(static fn($mailbox) => $mailbox->invalidate())
            ->map(static fn() => new SideEffect);
        $this->mailboxes = $this->mailboxes->remove($name->toString());

        return $deleted;
    }

    /**
     * @internal
     */
    public function terminate(): void
    {
        $_ = $this->mailboxes->foreach(
            static fn($_, $mailbox) => $mailbox->invalidate(),
        );
        $this->mailboxes = $this->mailboxes->clear();
        $this->valid = false;
    }
}
