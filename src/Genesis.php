<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Actor\Address\Name,
    Message\Init,
    Message\Tell,
};
use Innmind\Mantle\Forerunner;
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\Immutable\{
    Maybe,
    Map,
    Sequence,
    SideEffect,
};

final class Genesis
{
    /**
     * @param Map<class-string<Actor>, callable(Message, Spawn): Actor> $factories
     */
    private function __construct(
        private OperatingSystem $os,
        private Mailboxes $mailboxes,
        private Scheduled $scheduled,
        private Map $factories,
    ) {
    }

    public static function of(
        OperatingSystem $os,
        Mailboxes $mailboxes,
        Scheduled $scheduled,
    ): self {
        return new self(
            $os,
            $mailboxes,
            $scheduled,
            Map::of(),
        );
    }

    /**
     * @psalm-mutation-free
     * @template I of Message
     * @template A of Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $class
     * @param callable(A, Spawn): T $factory
     */
    public function actor(string $class, callable $factory): self
    {
        /** @psalm-suppress InvalidArgument Forced to lose type precision due to genericity of the Map */
        return new self(
            $this->os,
            $this->mailboxes,
            $this->scheduled,
            ($this->factories)($class, $factory),
        );
    }

    /**
     * @template I of Message
     * @template A of Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $root
     * @param A $argument
     *
     * @return Maybe<SideEffect>
     */
    public function run(string $root, Message $argument): Maybe
    {
        return $this
            ->mailboxes
            ->for($this->os, Name::root())
            ->flatMap(
                fn($mailbox) => $this
                    ->scheduled
                    ->push(
                        $this->os,
                        Name::root(),
                    )
                    ->map(static fn() => $mailbox->address()),
            )
            ->flatMap(static fn($address) => $address(Sequence::of(Init::of($root, $argument))))
            ->map($this->loop(...));
    }

    private function loop(): SideEffect
    {
        $loop = Forerunner::of($this->os);

        return $loop(
            new SideEffect,
            Supervisor::of(
                $this->scheduled,
                $this->mailboxes,
                Factories::of($this->factories),
                Denormalize::of(
                    Init::class,
                    Tell::class,
                ),
            ),
        );
    }
}
