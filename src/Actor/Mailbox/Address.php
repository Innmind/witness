<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox;

use Innmind\Witness\{
    Message,
    Signal,
};

/**
 * @template H of Message
 */
interface Address
{
    /**
     * @param H $message
     */
    public function __invoke(Message $message): void;

    /**
     * @internal
     */
    public function signal(Signal\ChildFailed|Signal\Terminated $signal): void;

    /**
     * @internal
     */
    public function toString(): string;
}
