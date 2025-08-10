<?php
declare(strict_types = 1);

namespace Innmind\Actors;

/**
 * @template M of Message
 * @template A of ?Message
 */
interface Actor
{
    public function __invoke(Receive $receive): Receive;
}
