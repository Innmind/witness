<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message\Payload;

use Innmind\TimeContinuum\Format;

/**
 * @psalm-immutable
 */
final class PointInTimeFormat implements Format
{
    public function toString(): string
    {
        return 'Y-m-d\TH:i:s.uP';
    }
}
