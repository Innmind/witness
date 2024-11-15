<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Witness\Actor;
use Innmind\Immutable\Sequence;

final class Actors
{
    /** @var Sequence<Actor> */
    private Sequence $scheduled;

    public function __construct()
    {
        $this->scheduled = Sequence::of();
    }

    public function schedule(Actor $actor): void
    {
        $this->scheduled = ($this->scheduled)($actor);
    }

    /**
     * @return Sequence<Actor>
     */
    public function scheduled(): Sequence
    {
        $scheduled = $this->scheduled;
        $this->scheduled = $this->scheduled->clear();

        return $scheduled;
    }
}
