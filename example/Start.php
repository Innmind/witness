<?php
declare(strict_types = 1);

namespace Example;

use Innmind\Witness\Message;
use Innmind\Immutable\Maybe;

final class Start implements Message
{
    public function get(string $key): Maybe
    {
        return Maybe::nothing();
    }
}
