<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor;

use Innmind\Witness\{
    Message,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    SideEffect,
};

interface Mailbox
{
    public function address(): Address;

    /**
     * @param Sequence<Message> $messages
     *
     * @return Maybe<SideEffect>
     */
    public function push(OperatingSystem $os, Sequence $messages): Maybe;

    /**
     * @return Maybe<Message>
     */
    public function pull(OperatingSystem $os): Maybe;
}
