<?php
declare(strict_types = 1);

namespace Innmind\Actors\Actor\Mailbox\Consume;

use Innmind\Actors\Actor\Mailbox\Consume;

final class Always implements Consume
{
    public function __invoke(): bool
    {
        return true;
    }
}
