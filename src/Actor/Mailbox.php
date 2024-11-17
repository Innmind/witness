<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor;

use Innmind\Witness\Message\Payload\Serialized;
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    SideEffect,
};

interface Mailbox
{
    public function address(Address\Name $sender): Address;

    /**
     * Trying to push to a no longer existent mailbox should return a SideEffect
     * as the sender can't know if the address it has is still valid or not.
     *
     * @param Sequence<Serialized> $messages
     *
     * @return Maybe<SideEffect>
     */
    public function push(Sequence $messages): Maybe;

    /**
     * @param ?Period $max The max period to wait for a message
     *
     * @return Maybe<Serialized>
     */
    public function pull(?Period $max = null): Maybe;
}
