<?php
declare(strict_types = 1);

namespace Innmind\Actors\Supervisor;

/**
 * @internal
 */
final class DeadRootActor
{
    private function __construct()
    {
    }

    public static function failed(): self
    {
        return new self;
    }

    public static function stopped(): self
    {
        return new self;
    }

    public static function mailboxNotFound(): self
    {
        return new self;
    }
}
