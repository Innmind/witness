<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor;

use Innmind\Witness\{
    Actor,
    Message,
};
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

/**
 * @template T of Actor
 */
interface Address
{
    /**
     * @template M of Message
     * @template I of T<M>
     *
     * @param M $message
     *
     * @return Maybe<SideEffect>
     */
    public function __invoke(Message $message): Maybe;
}
