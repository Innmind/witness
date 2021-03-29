<?php
declare(strict_types = 1);

namespace Innmind\Witness\Signal;

use Innmind\Witness\Signal;

/**
 * An actor will receive this signal after it asked to stop itself
 */
final class PostStop implements Signal
{
}
