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
        print("$name joined.\n");
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
        // in real life don't print to the output
        $greet->get('name')->match(
            function($name): void {
                print("{$this->name}: Hi $name 👋\n");

                throw new \Exception('unhandled exception should restart the actor');
            },
            fn() => print("{$this->name}: Hi guys 🙂\n"),
        );
    }
}
