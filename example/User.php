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
            Signal\PostStop::class => print("{$this->name} disconnected.\n"),
            default => null, // discard other messages
        };
    }

    private function greet(Greet $greet): void
    {
        // in real life don't print to the output
        $greet->get('name')->match(
            function($name): void {
                if ($this->name === 'Alice' && $name === 'John') {
                    print("{$this->name}: I don't like $name, I'm outta here!\n");

                    throw new Stop;
                }

                print("{$this->name}: Hi $name 👋\n");

                throw new \Exception('unhandled exception should restart the actor');
            },
            fn() => print("{$this->name}: Hi guys 🙂\n"),
        );
    }
}
