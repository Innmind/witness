<?php
declare(strict_types = 1);

namespace Example;

use Innmind\Witness\{
    Genesis,
    Actor,
    Message,
    Signal,
};

final class User implements Actor
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function __invoke(Message|Signal $message): void
    {
        match(\get_class($message)) {
            Greet::class => $this->greet($message),
            default => null, // discard other messages
        };
    }

    private function greet(Greet $greet): void
    {
        // in real live don't print to the output
        $greet->get('name')->match(
            fn($name) => print("{$this->name}: Hi $name 👋\n"),
            fn() => print("{$this->name}: Hi guys 🙂\n"),
        );
    }
}
