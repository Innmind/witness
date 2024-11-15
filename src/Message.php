<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Message\Payload;

/**
 * @psalm-immutable
 */
interface Message
{
    public function normalize(): Payload;
}
