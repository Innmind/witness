<?php
declare(strict_types = 1);

namespace Example;

use Innmind\Witness\{
    Genesis,
    Actor,
    Message,
    Signal,
    Exception\Stop,
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
        match(\get_class($message)) {
            Start::class => $this->plan(),
            Signal\Terminated::class => throw new Stop,
            Signal\PostStop::class => print("Chat killed\n"),
            default => null, // discard other messages
        };
    }

    private function plan(): void
    {
        $group = $this->system->spawn(Group::class);
        $group(new Add('Alice'));
        $group(new Add('Bob'));
        $group(new Add('Jane'));
        $group(new Add('John'));
    }
}
