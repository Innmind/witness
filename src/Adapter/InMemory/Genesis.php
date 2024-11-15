<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Witness\{
    Genesis as GenesisInterface,
    Message,
    Actor,
    Actor\Address,
    Spawn,
};
use Innmind\Mantle\Forerunner;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    Map,
    SideEffect,
};

final class Genesis implements GenesisInterface
{
    /**
     * @param Map<class-string<Actor>, callable(Message, Address, Spawn): Actor> $factories
     */
    private function __construct(
        private OperatingSystem $os,
        private Map $factories,
    ) {
    }

    public static function of(OperatingSystem $os): self
    {
        return new self($os, Map::of());
    }

    /**
     * @psalm-mutation-free
     * @template I of Message
     * @template A of Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $class
     * @param callable(A, Address<T>, Spawn): T $factory
     */
    public function actor(string $class, callable $factory): self
    {
        /** @psalm-suppress InvalidArgument We're forced to lose type precision in the Map of actors */
        return new self(
            $this->os,
            ($this->factories)($class, $factory),
        );
    }

    public function run(string $root, Message $argument): Maybe
    {
        $spawn = new namespace\Spawn(
            $this->factories,
            Mailboxes::new(),
            $actors = new Actors,
        );

        return $spawn($root, $argument)
            ->map(fn() => $this->loop($spawn, $actors))
            ->map(static fn() => new SideEffect);
    }

    private function loop(
        namespace\Spawn $spawn,
        Actors $actors,
    ): void {
        $loop = Forerunner::of($this->os);

        /** @psalm-suppress MixedArgumentTypeCoercion Todo see why it complains later */
        $loop(
            null,
            new Source($spawn, $actors),
        );
    }
}
