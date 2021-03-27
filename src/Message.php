<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Actor\Mailbox\Address;
use Innmind\Immutable\Maybe;

/**
 * @psalm-type Primitive = int|float|string|bool
 * @psalm-type Value = Primitive|Address
 * @psalm-type Collection = list<Value>|array<string, Value>
 * @psalm-type T = Collection|Value
 * @psalm-immutable
 */
interface Message
{
    /**
     * @return Maybe<T>
     */
    public function get(string $key): Maybe;
}
