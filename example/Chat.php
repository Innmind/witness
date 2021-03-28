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
    private Genesis $system;

    public function __construct(Genesis $system)
    {
        $this->system = $system;
    }

    public function __invoke(Message|Signal $message): void
    {
        if (!$message instanceof Start) {
            return;
        }

        $group = $this->system->spawn(Group::class);
        $group(new Add('Alice'));
        $group(new Add('Bob'));
        $group(new Add('Jane'));
        $group(new Add('John'));
    }
}
