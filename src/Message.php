<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Message\Payload;
use Innmind\Immutable\Maybe;

interface Message
{
    /**
     * @return Maybe<self>
     */
    public static function denormalize(Payload $payload): Maybe;
    public function normalize(): Payload;
}
