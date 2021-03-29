<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox;

use Innmind\Witness\{
    Actor,
    Actor\Mailbox,
    Genesis\InMemory\Children,
    Message,
    Signal,
    Signal\PreRestart,
    Signal\PostStop,
    Signal\ChildFailed,
    Signal\Terminated,
    Exception\Stop,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

final class InMemory implements Mailbox
{
    /** @var callable(): Actor<Message> */
    private $factory;
    /** @var callable(ChildFailed|Terminated): void */
    private $signal;
    private Children $children;
    /** @var Maybe<Actor<Message>> */
    private Maybe $actor;
    /** @var Sequence<Message|Signal> */
    private Sequence $messages;
    /** @var Address<Message> */
    private Address $address;
    private bool $stopping = false;

    /**
     * @param callable(): Actor<Message> $factory
     * @param callable(ChildFailed|Terminated): void $signal
     */
    public function __construct(
        callable $factory,
        callable $signal,
        Children $children
    ) {
        $this->factory = $factory;
        $this->signal = $signal;
        $this->children = $children;
        /** @var Maybe<Actor<Message>> */
        $this->actor = Maybe::nothing();
        /** @var Sequence<Message|Signal> */
        $this->messages = Sequence::of(Message::class.'|'.Signal::class);
        /** @var Address<Message> */
        $this->address = new Address\InMemory(function(Message|Signal $message): void {
            $this->publish($message);
        });
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function consume(Consume $continue): Maybe
    {
        try {
            $this->doConsume($continue);

            /** @var Maybe<Mailbox> */
            return Maybe::just($this);
        } catch (Stop $e) {
            /** @var Maybe<Mailbox> */
            return Maybe::nothing();
        }
    }

    public function stop(): void
    {
        $this->stopping = true;
        $this->children->stop();
    }

    private function doConsume(Consume $continue): void
    {
        $messages = $this->filter();
        // clearing the queue in the property as running the actor may add
        // messages to its own queue and not clearing it whould remove the new
        // messages. New messages are readded at the end of this function
        $this->messages = $this->messages->clear();

        while ($continue() && !$messages->empty()) {
            $this->actor = $messages
                ->take(1)
                ->reduce(
                    $this->actor,
                    function(Maybe $actor, Message|Signal $message): Maybe {
                        /** @var Maybe<Actor<Message>> $actor */
                        $this->attemptDefinitiveStop($actor, $message);
                        $this->garbageCollectChildren($message);

                        if ($this->stopping) {
                            $this->trap($message);

                            return $actor;
                        }

                        return $this
                            ->start($actor)
                            ->flatMap(function(Actor $actor) use ($message) {
                                try {
                                    $actor($message);

                                    /** @var Maybe<Actor<Message>> */
                                    return Maybe::just($actor);
                                } catch (Stop $e) {
                                    $this->stop();

                                    /** @var Maybe<Actor<Message>> */
                                    return Maybe::just($actor);
                                } catch (\Throwable $e) {
                                    try {
                                        $actor(new PreRestart);
                                    } catch (\Throwable $e) {
                                        // discard an error when gracefully shutting down an actor
                                    }

                                    ($this->signal)(ChildFailed::of($this->address));

                                    /** @var Maybe<Actor<Message>> */
                                    return Maybe::nothing();
                                }
                            });
                    },
                );
            $messages = $messages->drop(1);
        }

        $this->messages = $messages->append($this->messages);
    }

    private function publish(Message|Signal $message): void
    {
        if ($this->stopping && $message instanceof Message) {
            // don't publish new messages to an actor waiting to be killed
            return;
        }

        $this->messages = ($this->messages)($message);
    }

    /**
     * @param Maybe<Actor<Message>> $actor
     *
     * @return Maybe<Actor<Message>>
     */
    private function start(Maybe $actor): Maybe
    {
        return $actor->otherwise(fn() => Maybe::just(($this->factory)()));
    }

    /**
     * Only keep the signals when the actor is shutting down
     *
     * @return Sequence<Message|Signal>
     */
    private function filter(): Sequence
    {
        if (!$this->stopping) {
            return $this->messages;
        }

        if ($this->children->empty()) {
            // when all children are stopped then we can stop the parent
            return $this->messages->clear()(new PostStop);
        }

        return $this->messages->filter(
            static fn($message) => $message instanceof Signal,
        );
    }

    /**
     * @param Maybe<Actor<Message>> $actor
     *
     * @throws Stop This will definitvely kill the mailbox
     */
    private function attemptDefinitiveStop(Maybe $actor, Message|Signal $message): void
    {
        if (!($message instanceof PostStop)) {
            return;
        }

        $stop = $actor->match(
            static fn($actor) => static fn() => $actor($message),
            static fn() => static fn() => null,
        );

        try {
            $stop();
        } finally {
            ($this->signal)(Terminated::of($this->address));

            throw new Stop;
        }
    }

    /**
     * This method is called when handling a message while the actor is shutting
     * down to discard all messages an react to signals coming from
     */
    private function trap(Message|Signal $message): void
    {
        if ($message instanceof ChildFailed || $message instanceof Terminated) {
            // hijack the fault tolerance mechanism so the actor doesn't try to
            // restart its children when waiting to be stopped itself
            $this->children->unregister($message->child());
        }
    }

    private function garbageCollectChildren(Message|Signal $message): void
    {
        if ($message instanceof Terminated) {
            $this->children->unregister($message->child());
        }
    }
}
