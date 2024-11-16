<?php
declare(strict_types = 1);

namespace Innmind\Witness\Signal;

use Innmind\Witness\Actor\Address\Name;

/**
 * @psalm-immutable
 * @internal
 */
final class Terminated
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
