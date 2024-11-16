<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor;

use Innmind\Witness\Message\Payload\Serialized;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    SideEffect,
};

interface Mailbox
{
    public function address(): Address;

    /**
     * @param Sequence<Serialized> $messages
     *
     * @return Maybe<SideEffect>
     */
    public function push(Sequence $messages): Maybe;

    /**
     * @return Maybe<Serialized>
     */
    public function pull(): Maybe;
}
