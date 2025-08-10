<?php
declare(strict_types = 1);

namespace Innmind\Actors;

use Innmind\Actors\Adapter\{
    Mailboxes,
    Scheduled,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

interface Adapter
{
    public function mailboxes(): Mailboxes;
    public function scheduled(): Scheduled;

    /**
     * @return Maybe<SideEffect>
     */
    public function terminate(OperatingSystem $os): Maybe;
}
