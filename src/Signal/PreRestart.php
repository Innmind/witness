<?php
declare(strict_types = 1);

namespace Innmind\Witness\Signal;

use Innmind\Witness\Signal;

/**
 * An actor will receive this signal after throwing an unexpected exception
 * to allow it to clean its resources before being restarted
 */
final class PreRestart implements Signal
{
}
