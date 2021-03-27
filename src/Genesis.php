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
     * @param class-string<Actor> $actor
     * @param T $args
     */
    public function spawn(string $actor, ...$args): Address;
}
