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
 * @template M of Message
 * @template T of Actor<M>
 */
interface Address
{
    /**
     * @param M $message
     *
     * @return Maybe<SideEffect>
     */
    public function __invoke(Message $message): Maybe;
}
