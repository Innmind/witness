<?php
declare(strict_types = 1);

namespace Innmind\Actors\Signal;

use Innmind\Actors\{
    Signal,
    Actor\Mailbox\Address,
};

/**
 * An actor will receive this signal when one of its children stops
 */
final class Terminated implements Signal
{
    private Address $child;

    private function __construct(Address $child)
    {
        $this->child = $child;
    }

    public static function of(Address $child): self
    {
        return new self($child);
    }

    public function child(): Address
    {
        return $this->child;
    }
}
