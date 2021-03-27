<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Actor\Mailbox\Address;

/**
 * @psalm-type Primitive = int|float|string|bool
 * @psalm-type Value = Primitive|Address
 * @psalm-type Collection = list<Value>|array<string, Value>
 * @psalm-type T = Collection|Value
 */
interface Genesis
{
    /**
     * @template H of Message
     * @template A of Actor<H>
     *
     * @param class-string<A> $actor
     * @param T $args
     *
     * @return Address<H>
     */
    public function spawn(string $actor, ...$args): Address;
}
