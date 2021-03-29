<?php
declare(strict_types = 1);

namespace Example;

use Innmind\Witness\Message;
use Innmind\Immutable\Maybe;

final class Add implements Message
{
    private string $user;

    public function __construct(string $user)
    {
        $this->user = $user;
    }

    public function get(string $key): Maybe
    {
        return match($key) {
            'user' => Maybe::just($this->user),
        };
    }
}
