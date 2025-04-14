<?php
declare(strict_types = 1);

namespace Innmind\Witness\Signal\Child;

use Innmind\Witness\Actor\Address\Name;

/**
 * @psalm-immutable
 * @internal
 */
final class Termination
{
    private function __construct(
        private Name $child,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(Name $child): self
    {
        return new self($child);
    }

    public function child(): Name
    {
        return $this->child;
    }
}
