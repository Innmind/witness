<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox;

use Innmind\Witness\Message;

interface Address
{
    public function __invoke(Message $message): void;

    /**
     * @internal
     */
    public function toString(): string;
}