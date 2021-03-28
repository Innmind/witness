<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Exception\Stop;

/**
 * @template H of Message
 */
interface Actor
{
    /**
     * @param H|Signal $message
     *
     * @throws Stop To remove the actor from the system
     */
    public function __invoke(Message|Signal $message): void;
}
