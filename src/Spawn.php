<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Actor\Address;
use Innmind\Immutable\Maybe;

interface Spawn
{
    /**
     * @template I of Message
     * @template A of Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $actor
     * @param A $argument
     *
     * @return Maybe<Address<T>>
     */
    public function __invoke(string $actor, Message $argument): Maybe;
}
