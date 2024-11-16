<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Message\Payload;
use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
interface Message
{
    /**
     * @psalm-pure
     *
     * @return Maybe<self>
     */
    public static function denormalize(
        Denormalize $denormalize,
        Payload $payload,
    ): Maybe;
    public function normalize(): Payload;
}
