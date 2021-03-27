<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox\Address;

use Innmind\Witness\{
    Actor\Mailbox,
    Actor\Mailbox\Address,
    Message,
};

final class InMemory implements Address
{
    private Mailbox $mailbox;

    public function __construct(Mailbox $mailbox)
    {
        $this->mailbox = $mailbox;
    }

    public function __invoke(Message $message): void
    {
        $this->mailbox->publish($message);
    }

    public function toString(): string
    {
        return '';
    }
}
