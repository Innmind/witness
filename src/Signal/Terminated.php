<?php
declare(strict_types = 1);

namespace Innmind\Witness\Signal;

use Innmind\Witness\{
    Actor,
    Actor\Address,
    Message,
};

final class Terminated
{
    /**
     * @param Address<Message, Actor<Message, Message>> $child
     */
    private function __construct(
        private Address $child,
    ) {
    }

    /**
     * @param Address<Message, Actor<Message, Message>> $child
     */
    public static function of(Address $child): self
    {
        return new self($child);
    }

    public function child(): Address
    {
        return $this->child;
    }
}
