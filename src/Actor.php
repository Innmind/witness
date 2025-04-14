<?php
declare(strict_types = 1);

namespace Innmind\Witness;

/**
 * @template M of Message
 * @template A of ?Message
 */
interface Actor
{
    public function __invoke(Receive $receive): Receive;
}
