<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Witness\{
    Actor,
    Actor\Address as AddressInterface,
    Message,
};
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

/**
 * @implements AddressInterface<Actor>
 */
final class Address implements AddressInterface
{
    public function __invoke(Message $message): Maybe
    {
        return Maybe::just(new SideEffect);
    }
}
