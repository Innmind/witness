<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor;

use Innmind\Witness\{
    Message,
    Actor\Mailbox\Address,
    Actor\Mailbox\Consume,
};

interface Mailbox
{
    /**
     * @return Address<Message>
     */
    public function address(): Address;
    public function consume(Consume $continue): void;
}
