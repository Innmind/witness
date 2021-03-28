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
    /** @var Address<Message> */
    private Address $address;

    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
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
            $this
                ->messages
                ->take(1)
                ->foreach(fn($message) => ($this->actor)($message));
            $this->messages = $this->messages->drop(1);
        }
    }

    private function publish(Message $message): void
    {
        $this->messages = ($this->messages)($message);
    }
}
