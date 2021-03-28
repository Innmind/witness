<?php
declare(strict_types = 1);

namespace Innmind\Witness\Genesis;

use Innmind\Witness\{
    Genesis,
    Actor\Mailbox,
    Actor\Mailbox\Address,
    Actor\Mailbox\Consume,
    Actor,
    Message,
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
    /** @var Map<string, callable> */
    private Map $factories;
    /** @var Map<Address<Message>, Set<Address<Message>>> */
    private Map $children;
    /** @var Maybe<Address<Message>> */
    private Maybe $running;

    public function __construct()
    {
        $this->mailboxes = Set::of(Mailbox::class);
        $this->factories = Map::of('string', 'callable');
        /** @var Map<Address<Message>, Set<Address<Message>>> */
        $this->children = Map::of(Address::class, Set::class);
        /** @var Maybe<Address<Message>> */
        $this->running = Maybe::nothing();
    }

    /**
     * @param class-string<Actor<Message>> $class
     * @param callable(Genesis, Set<Address>, ...T): Actor<Message> $factory
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
        $mailbox = new Mailbox\InMemory(function() use ($actor, $args): Actor {
            /** @var A */
            return $this->factories->get($actor)(
                $this,
                $this->running->match(
                    fn($parent) => $this->children->get($parent),
                    fn() => Set::of(Address::class),
                ),
                ...$args,
            );
        });
        $this->mailboxes = ($this->mailboxes)($mailbox);
        /** @var Address<H> */
        $address = $mailbox->address();
        $this->children = ($this->children)($address, Set::of(Address::class));
        $this->children = $this->running->match(
            fn($parent) => ($this->children)(
                $parent,
                $this->children->get($parent)($address),
            ),
            fn() => $this->children,
        );

        return $address;
    }

    public function run(): void
    {
        $continue = fn(): Consume => new Consume\Always;

        while (true) {
            $this->mailboxes->foreach(function($mailbox) use ($continue): void {
                $this->running = Maybe::just($mailbox->address());
                $mailbox->consume($continue());
            });
        }
    }
}
