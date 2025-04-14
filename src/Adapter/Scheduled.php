<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter;

use Innmind\Witness\Actor\Address\Name;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

interface Scheduled
{
    /**
     * @return Maybe<SideEffect>
     */
    public function push(OperatingSystem $os, Name $address): Maybe;

    /**
     * @return Maybe<Name>
     */
    public function pull(OperatingSystem $os): Maybe;
}
