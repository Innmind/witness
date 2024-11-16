<?php
declare(strict_types = 1);

namespace Innmind\Witness\Spawn;

/**
 * @internal
 */
final class Counter
{
    private function __construct(
        private int $children,
    ) {
    }

    public static function start(): self
    {
        return new self(0);
    }

    public function increment(): void
    {
        ++$this->children;
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return T
     */
    public function decrement(mixed $value): mixed
    {
        --$this->children;

        return $value;
    }

    public function childless(): bool
    {
        // Checking negative values in case we receive more Terminated signals
        // than spawned children. But this should not happen.
        return $this->children <= 0;
    }
}
