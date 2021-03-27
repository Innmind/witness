<?php
declare(strict_types = 1);

namespace Innmind\Witness;

final class Signal
{
    /**
     * An actor will receive this signal after it asked to stop itself
     */
    public static function postStop(): self
    {
        return new self;
    }

    /**
     * An actor will receive this signal when one of its children stops
     */
    public static function terminated(): self
    {
        return new self;
    }

    /**
     * An actor will receive this signal after throwing an unexpected exception
     * to allow it to clean its resources before being restarted
     */
    public static function preRestart(): self
    {
        return new self;
    }

    /**
     * An actor will receive this signal when one of its children has thrown an
     * unexpected exception
     */
    public static function childFailed(): self
    {
        return new self;
    }
}
