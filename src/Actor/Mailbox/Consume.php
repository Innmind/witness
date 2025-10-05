<?php
declare(strict_types = 1);

namespace Innmind\Actors\Actor\Mailbox;

interface Consume
{
    public function __invoke(): bool;
}
