<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Actor\Address\Name,
    Actor\Mailbox,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

interface Mailboxes
{
    /**
     * @return Maybe<Mailbox>
     */
    public function for(OperatingSystem $os, Name $name): Maybe;

    /**
     * @return Maybe<SideEffect>
     */
    public function delete(Name $name): Maybe;
}
