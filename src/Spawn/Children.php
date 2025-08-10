<?php
declare(strict_types = 1);

namespace Innmind\Actors\Spawn;

use Innmind\Actors\{
    Actor\Address,
    Actor\Address\Name,
};
use Innmind\Immutable\Sequence;

/**
 * @internal
 */
final class Children
{
    /**
     * @param Sequence<Address> $children
     */
    private function __construct(
        private Sequence $children,
    ) {
    }

    public static function start(): self
    {
        return new self(Sequence::of());
    }

    public function add(Address $child): Address
    {
        $this->children = ($this->children)($child);

        return $child;
    }

    public function remove(Name $terminated): Name
    {
        $this->children = $this->children->exclude(
            static fn($child) => $child->name()->equals($terminated),
        );

        return $terminated;
    }

    public function childless(): bool
    {
        return $this->children->empty();
    }

    /**
     * @return Sequence<Address>
     */
    public function list(): Sequence
    {
        return $this->children;
    }
}
