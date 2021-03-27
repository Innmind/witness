<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox;

use Innmind\Witness\{
    Actor,
    Actor\Mailbox,
    Message,
};
use Innmind\Immutable\Sequence;

final class InMemory implements Mailbox
{
    private Actor $actor;
    /** @var Sequence<Message> */
    private Sequence $messages;

    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
        /** @var Sequence<Message> */
        $this->messages = Sequence::of(Message::class);
    }

    public function publish(Message $message): void
    {
        $this->messages = ($this->messages)($message);
    }

    public function consume(Consume $continue): void
    {
        while($continue()) {
            $this
                ->messages
                ->take(1)
                ->foreach(fn($message) => ($this->actor)($message));
            $this->messages = $this->messages->drop(1);
        }
    }
}
