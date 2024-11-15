<?php
declare(strict_types = 1);

namespace Innmind\Witness\Receive;

final class Continuation
{
    public function continue(): self
    {
        return $this;
    }

    public function stop(): self
    {
        return $this;
    }
}
