<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Actor\Address,
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
    public function generate(OperatingSystem $os): Maybe;

    /**
     * @return Maybe<Mailbox>
     */
    public function for(OperatingSystem $os, Name $name): Maybe;

    /**
     * This method allows to expose an address even if the associated mailbox
     * doesn't exist. This is useful to expose addresses in messages that
     * represent actors that have terminated since the message containing the
     * address was emitted.
     *
     * For all other usages self::for() must be used.
     */
    public function address(
        OperatingSystem $os,
        Name $name,
        Name $currentActor,
    ): Address;

    /**
     * @return Maybe<SideEffect>
     */
    public function delete(Name $name): Maybe;
}
