<?php
declare(strict_types = 1);

namespace Innmind\Witness\Genesis;

use Innmind\Witness\{
    Genesis,
    Genesis\InMemory\Children,
    Actor\Mailbox,
    Actor\Mailbox\Address,
    Actor\Mailbox\Consume,
    Actor,
    Message,
    Signal,
};
use Innmind\Immutable\{
    Map,
    Set,
    Maybe,
};

/**
 * @psalm-import-type T from Genesis
 */
final class InMemory implements Genesis
{
    /** @var Set<Mailbox> */
    private Set $mailboxes;
    /** @var Set<Mailbox> */
    private Set $newMailboxes;
    /** @var Map<string, callable> */
    private Map $factories;
    /** @var Map<Address<Message>, Children> */
    private Map $children;
    /** @var Maybe<Address<Message>> */
    private Maybe $running;

    public function __construct()
    {
        $this->mailboxes = Set::of(Mailbox::class);
        $this->newMailboxes = Set::of(Mailbox::class);
        $this->factories = Map::of('string', 'callable');
        /** @var Map<Address<Message>, Children> */
        $this->children = Map::of(Address::class, Children::class);
        /** @var Maybe<Address<Message>> */
        $this->running = Maybe::nothing();
    }

    /**
     * @param class-string<Actor<Message>> $class
     * @param callable(Genesis, Set<Address<Message>>, ...T): Actor<Message> $factory
     */
    public function actor(string $class, callable $factory): self
    {
        $self = clone $this;
        $self->factories = ($this->factories)($class, $factory);

        return $self;
    }

    /**
     * @template H of Message
     * @template A of Actor<H>
     *
     * @param class-string<A> $actor
     * @param T $args
     *
     * @return Address<H>
     */
    public function spawn(string $actor, ...$args): Address
    {
        $parent = $this->running;
        $children = new Children;
        $mailbox = new Mailbox\InMemory(
            function() use ($children, $actor, $args): Actor {
                /** @var A */
                return $this->factories->get($actor)(
                    $this,
                    $children->addresses(),
                    ...$args,
                );
            },
            function(Signal\ChildFailed|Signal\Terminated $signal) use ($parent): void {
                $this->signal($parent, $signal);
            },
            $children,
        );
        $this->newMailboxes = ($this->newMailboxes)($mailbox);
        /** @var Address<H> */
        $address = $mailbox->address();
        $this->children = ($this->children)($address, $children);
        $this->running->match(
            fn($parent) => $this->children->get($parent)->register($mailbox),
            fn() => null, // nothing to do
        );

        return $address;
    }

    public function run(): void
    {
        $continue = fn(): Consume => new Consume\Always;

        do {
            $newMailboxes = $this->newMailboxes;
            $this->newMailboxes = $this->newMailboxes->clear();
            $this->mailboxes = $this
                ->mailboxes
                ->merge($newMailboxes)
                ->reduce(
                    $this->mailboxes->clear(),
                    function(Set $mailboxes, Mailbox $mailbox) use ($continue): Set {
                        $this->running = Maybe::just($mailbox->address());

                        /** @var Set<Mailbox> */
                        return $mailbox->consume($continue())->match(
                            fn(Mailbox $mailbox): Set => ($mailboxes)($mailbox),
                            fn(): Set => $mailboxes,
                        );
                    },
                );
            // todo garbage collect the children map
        } while (!$this->mailboxes->empty());
    }

    /**
     * @param Maybe<Address<Message>> $parent
     */
    private function signal(
        Maybe $parent,
        Signal\ChildFailed|Signal\Terminated $signal
    ): void {
        $parent->match(
            fn(Address $parent) => $parent->signal($signal),
            fn() => null, // this is the case for the root actor
        );
    }
}
