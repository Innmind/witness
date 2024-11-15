<?php
declare(strict_types = 1);

namespace Innmind\Witness\Signal;

use Innmind\Witness\Actor\Address;

final class ChildFailed
{
    private function __construct(
        private Address $child,
    ) {
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
