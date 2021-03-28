<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox;

use Innmind\Witness\{
    Actor,
    Actor\Mailbox,
    Message,
    Signal\PreRestart,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
};

final class InMemory implements Mailbox
{
    /** @var callable(): Actor<Message> */
    private $factory;
    /** @var Maybe<Actor<Message>> */
    private Maybe $actor;
    /** @var Sequence<Message> */
    private Sequence $messages;
    /** @var Address<Message> */
    private Address $address;

    /**
     * @param callable(): Actor<Message> $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
        /** @var Maybe<Actor<Message>> */
        $this->actor = Maybe::nothing();
        /** @var Sequence<Message> */
        $this->messages = Sequence::of(Message::class);
        /** @var Address<Message> */
        $this->address = new Address\InMemory(function(Message $message): void {
            $this->publish($message);
        });
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function consume(Consume $continue): void
    {
        while($continue() && !$this->messages->empty()) {
            $this->actor = $this
                ->messages
                ->take(1)
                ->reduce(
                    $this->actor,
                    function(Maybe $actor, Message $message): Maybe {
                        /** @var Maybe<Actor<Message>> $actor */

                        return $this
                            ->start($actor)
                            ->flatMap(function(Actor $actor) use ($message) {
                                try {
                                    $actor($message);

                                    /** @var Maybe<Actor<Message>> */
                                    return Maybe::just($actor);
                                } catch (\Throwable $e) {
                                    $actor(new PreRestart);
                                    // todo notify the parent actor

                                    /** @var Maybe<Actor<Message>> */
                                    return Maybe::nothing();
                                }
                            });
                    },
                );
            $this->messages = $this->messages->drop(1);
        }
    }

    private function publish(Message $message): void
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
