<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Witness\{
    Adapter\Scheduled as ScheduledInterface,
    Actor\Address\Name,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    SideEffect,
};

final class Scheduled implements ScheduledInterface
{
    /**
     * @param Sequence<Name> $actors
     */
    private function __construct(
        private Sequence $actors,
        private bool $valid,
    ) {
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self(Sequence::of(), true);
    }

    public function push(OperatingSystem $os, Name $address): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        // todo pair of sockets
        $this->actors = ($this->actors)($address);

        return Maybe::just(new SideEffect);
    }

    public function pull(OperatingSystem $os): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<Name> */
            return Maybe::nothing();
        }

        // todo pair of sockets
        $first = $this->actors->first();
        $this->actors = $this->actors->drop(1);

        return $first;
    }

    /**
     * @internal
     */
    public function terminate(): void
    {
        $this->actors = $this->actors->clear();
        $this->valid = false;
    }
}
