<?php
declare(strict_types = 1);

namespace Innmind\Witness;

/**
 * @template H of Message
 */
interface Actor
{
    /**
     * @param H|Signal $message
     */
    public function __invoke(Message|Signal $message): void;
}
