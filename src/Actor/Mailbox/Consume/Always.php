<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox\Consume;

use Innmind\Witness\Actor\Mailbox\Consume;

final class Always implements Consume
{
    public function __invoke(): bool
    {
        return true;
    }
}
