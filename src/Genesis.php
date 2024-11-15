<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Actor\Address;
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

interface Genesis
{
    /**
     * @psalm-mutation-free
     * @template I of Message
     * @template A of Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $class
     * @param callable(A, Address<T>, Spawn): T $factory
     */
    public function actor(string $class, callable $factory): self;

    /**
     * @template I of Message
     * @template A of Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $root
     * @param A $argument
     *
     * @return Maybe<SideEffect>
     */
    public function run(string $root, Message $argument): Maybe;
}
