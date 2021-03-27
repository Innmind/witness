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

    public function __construct()
    {
        $this->mailboxes = Set::of(Mailbox::class);
        $this->factories = Map::of('string', 'callable');
    }

    /**
     * @param class-string<Actor<Message>> $class
     * @param callable(Genesis, ...T): Actor<Message> $factory
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
        /** @var A */
        $actor = $this->factories->get($actor)($this, ...$args);
        $mailbox = new Mailbox\InMemory($actor);
        $this->mailboxes = ($this->mailboxes)($mailbox);

        /** @var Address<H> */
        return new Address\InMemory($mailbox);
    }

    public function run(): void
    {
        $continue = fn(): Consume => new Consume\Once;

        while (true) {
            $this->mailboxes->foreach(
                static fn($mailbox) => $mailbox->consume($continue()),
            );
        }
    }
}
