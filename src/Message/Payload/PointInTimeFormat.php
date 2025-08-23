<?php
declare(strict_types = 1);

namespace Innmind\Actors\Message\Payload;

use Innmind\TimeContinuum\Format;

/**
 * @psalm-immutable
 */
final class PointInTimeFormat implements Format
{
    #[\Override]
    public function toString(): string
    {
        return 'Y-m-d\TH:i:s.uP';
    }
}
