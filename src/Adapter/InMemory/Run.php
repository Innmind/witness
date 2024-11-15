<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Witness\Actor;
use Innmind\OperatingSystem\OperatingSystem;

final class Run
{
    public function __construct(
        private OperatingSystem $os,
        private Actor $actor,
    ) {
    }

    public function __invoke(): void
    {

    }
}
