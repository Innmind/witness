<?php
declare(strict_types = 1);

namespace Innmind\Witness;

interface Actor
{
    public function __invoke(Message|Signal $message): void;
}
