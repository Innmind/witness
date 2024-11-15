<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Actor\Address\Name,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\Maybe;

interface Scheduled
{
    /**
     * @return Maybe<Name>
     */
    public function push(OperatingSystem $os, Name $address): Maybe;

    /**
     * @return Maybe<Name>
     */
    public function pull(OperatingSystem $os): Maybe;
}
