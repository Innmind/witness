<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Witness\{
    Spawn as SpawnInterface,
    Message,
    Actor,
    Actor\Address,
};
use Innmind\Immutable\{
    Maybe,
    Map,
};

/**
 * @internal
 */
final class Spawn implements SpawnInterface
{
    /**
     * @param Map<class-string<Actor>, callable(Message, Address, SpawnInterface): Actor> $factories
     */
    public function __construct(
        private Map $factories,
        private Mailboxes $mailboxes,
        private Actors $actors,
    ) {
    }

    /**
     * @template I of Message
     * @template A of Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $actor
     * @param A $argument
     *
     * @return Maybe<Address<T>>
     */
    public function __invoke(string $actor, Message $argument): Maybe
    {
        return $this
            ->factories
            ->get($actor)
            ->map(function($factory) use ($argument) {
                /** @var Address<T> */
                $address = $this->mailboxes->declare();
                /** @psalm-suppress InvalidArgument */
                $actor = $factory($argument, $address, $this);
                $this->actors->schedule($actor);

                return $address;
            });
    }
}
