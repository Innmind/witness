<?php
declare(strict_types = 1);

namespace Innmind\Actors\Signal;

use Innmind\Actors\Signal;

/**
 * An actor will receive this signal after throwing an unexpected exception
 * to allow it to clean its resources before being restarted
 */
final class PreRestart implements Signal
{
}
