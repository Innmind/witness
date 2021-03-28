<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox;

use Innmind\Witness\{
    Actor,
    Actor\Mailbox,
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
    /** @var Maybe<Actor<Message>> */
    private Maybe $actor;
    /** @var Sequence<Message|Signal> */
    private Sequence $messages;
    /** @var Address<Message> */
    private Address $address;

    /**
     * @param callable(): Actor<Message> $factory
     * @param callable(ChildFailed|Terminated): void $signal
     */
    public function __construct(callable $factory, callable $signal)
    {
        $this->factory = $factory;
        $this->signal = $signal;
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

    private function doConsume(Consume $continue): void
    {
        while($continue() && !$this->messages->empty()) {
            $this->actor = $this
                ->messages
                ->take(1)
                ->reduce(
                    $this->actor,
                    function(Maybe $actor, Message|Signal $message): Maybe {
                        /** @var Maybe<Actor<Message>> $actor */

                        return $this
                            ->start($actor)
                            ->flatMap(function(Actor $actor) use ($message) {
                                try {
                                    $actor($message);

                                    /** @var Maybe<Actor<Message>> */
                                    return Maybe::just($actor);
                                } catch (Stop $e) {
                                    $actor(new PostStop);
                                    ($this->signal)(Terminated::of($this->address));

                                    throw $e;
                                } catch (\Throwable $e) {
                                    $actor(new PreRestart);
                                    ($this->signal)(ChildFailed::of($this->address));

                                    /** @var Maybe<Actor<Message>> */
                                    return Maybe::nothing();
                                }
                            });
                    },
                );
            $this->messages = $this->messages->drop(1);
        }
    }

    private function publish(Message|Signal $message): void
    {
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
}
