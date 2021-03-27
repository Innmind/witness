<?php
declare(strict_types = 1);

namespace Example;

use Innmind\Witness\{
    Genesis,
    Actor,
    Message,
    Signal,
};

final class Chat implements Actor
{
    public function __construct(Genesis $system)
    {
        $group = $system->spawn(Group::class);
        $group(new Add('Alice'));
        $group(new Add('Bob'));
        $group(new Add('Jane'));
        $group(new Add('John'));
    }

    public function __invoke(Message|Signal $message): void
    {
        // this is the root actor and does not receive any message in this example
    }
}
