<?php
declare(strict_types = 1);

namespace Example;

use Innmind\Witness\{
    Genesis,
    Actor,
    Actor\Mailbox\Address,
    Message,
    Signal,
};
use Innmind\Immutable\Set;

final class Group implements Actor
{
    private Genesis $system;
    /** @var Set<Address> */
    private Set $users;

    public function __construct(Genesis $system)
    {
        $this->system = $system;
        $this->users = Set::of(Address::class);
    }

    public function __invoke(Message|Signal $message): void
    {
        match(\get_class($message)) {
            Add::class => $this->addUser($message),
            default => null, // discard other messages
        };
    }

    private function addUser(Add $add): void
    {
        $this->users = $add
            ->get('user')
            ->map(function(string $name): Address {
                $user = $this->system->spawn(User::class, $name);

                $this->users->foreach(fn($other) => $other(Greet::newcomer($name)));

                return $user;
            })
            ->map(function($user) {
                $user(Greet::all());

                return $user;
            })
            ->match(
                fn(Address $user) => ($this->users)($user),
                fn() => $this->users,
            );
    }
}
