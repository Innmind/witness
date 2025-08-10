<?php
declare(strict_types = 1);

namespace Innmind\Actors\Receive;

use Innmind\Actors\Message;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class Continuation
{
    /**
     * @param Sequence<Message> $messages
     */
    private function __construct(
        private bool $continue,
        private Sequence $messages,
    ) {
    }

    /**
     * @psalm-pure
     * @internal
     */
    public static function new(): self
    {
        return new self(true, Sequence::of());
    }

    /**
     * @param ?Sequence<Message> $messages
     */
    public function continue(?Sequence $messages = null): self
    {
        return new self(true, $messages ?? Sequence::of());
    }

    public function stop(): self
    {
        return new self(false, Sequence::of());
    }

    /**
     * @template C
     * @template S
     * @internal
     *
     * @param callable(Sequence<Message>): C $continue
     * @param callable(): S $stop
     *
     * @return C|S
     */
    public function match(callable $continue, callable $stop): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return match ($this->continue) {
            true => $continue($this->messages),
            false => $stop(),
        };
    }
}
