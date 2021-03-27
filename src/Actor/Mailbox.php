<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor;

use Innmind\Witness\{
    Message,
    Actor\Mailbox\Consume,
};

interface Mailbox
{
    public function publish(Message $message): void;
    public function consume(Consume $continue): void;
}
