<?php
declare(strict_types = 1);

namespace Innmind\Actors\Signal;

use Innmind\Actors\Signal;

/**
 * An actor will receive this signal after it asked to stop itself
 */
final class PostStop implements Signal
{
}
