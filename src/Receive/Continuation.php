<?php
declare(strict_types = 1);

namespace Innmind\Actors\Receive;

/**
 * @psalm-immutable
 */
final class Continuation
{
    private function __construct(
        private bool $continue,
    ) {
    }

    /**
     * @psalm-pure
     * @internal
     */
    public static function new(): self
    {
        return new self(true);
    }

    public function continue(): self
    {
        return new self(true);
    }

    public function stop(): self
    {
        return new self(false);
    }

    /**
     * @template C
     * @template S
     * @internal
     *
     * @param callable(): C $continue
     * @param callable(): S $stop
     *
     * @return C|S
     */
    public function match(callable $continue, callable $stop): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return match ($this->continue) {
            true => $continue(),
            false => $stop(),
        };
    }
}
