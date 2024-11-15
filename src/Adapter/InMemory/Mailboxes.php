<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Witness\{
    Actor\Address,
};

final class Mailboxes
{
    private function __construct()
    {
    }

    public static function new(): self
    {
        return new self;
    }

    public function declare(): Address
    {
        return new namespace\Address;
    }
}
