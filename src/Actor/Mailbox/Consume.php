<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox;

interface Consume
{
    public function __invoke(): bool;
}
