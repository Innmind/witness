<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter;

use Innmind\Witness\{
    Adapter,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    SideEffect,
};

final class InMemory implements Adapter
{
    private InMemory\Mailboxes $mailboxes;
    private InMemory\Scheduled $scheduled;

    private function __construct()
    {
        $this->mailboxes = InMemory\Mailboxes::new();
        $this->scheduled = InMemory\Scheduled::new();
    }

    public static function new(): self
    {
        return new self;
    }

    public function mailboxes(): Mailboxes
    {
        return $this->mailboxes;
    }

    public function scheduled(): Scheduled
    {
        return $this->scheduled;
    }

    public function terminate(OperatingSystem $os): Maybe
    {
        $this->mailboxes->terminate();
        $this->scheduled->terminate();

        return Maybe::just(new SideEffect);
    }
}
