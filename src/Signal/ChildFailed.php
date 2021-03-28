<?php
declare(strict_types = 1);

namespace Innmind\Witness\Signal;

use Innmind\Witness\{
    Signal,
    Actor\Mailbox\Address,
};

/**
 * An actor will receive this signal when one of its children has thrown an
 * unexpected exception
 */
final class ChildFailed implements Signal
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
