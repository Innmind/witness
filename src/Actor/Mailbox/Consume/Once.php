<?php
declare(strict_types = 1);

namespace Innmind\Actors\Actor\Mailbox\Consume;

use Innmind\Actors\Actor\Mailbox\Consume;

final class Once implements Consume
{
    private bool $continue = true;

    public function __invoke(): bool
    {
        if ($this->continue) {
            $this->continue = false;

            return true;
        }

        return false;
    }
}
