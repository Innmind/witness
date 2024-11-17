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
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Maybe,
    Map,
    Sequence,
    SideEffect,
};

final class Genesis
{
    /**
     * @param Map<class-string<Actor>, callable(OperatingSystem, ?Message, Spawn): Actor> $factories
     * @param Sequence<class-string<Message>> $messages
     */
    private function __construct(
        private OperatingSystem $os,
        private Adapter $adapter,
        private Map $factories,
        private Sequence $messages,
        private ?Period $terminationGrace,
    ) {
    }

    public static function of(
        OperatingSystem $os,
        Adapter $adapter,
        ?Period $terminationGrace = null,
    ): self {
        return new self(
            $os,
            $adapter,
            Map::of(),
            Sequence::of(),
            $terminationGrace,
        );
    }

    /**
     * @psalm-mutation-free
     * @template I of Message
     * @template A of ?Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $class
     * @param callable(OperatingSystem, A, Spawn): T $factory
     */
    public function actor(string $class, callable $factory): self
    {
        // use innmind/reflection ?
        $refl = new \ReflectionClass($class);
        $messages = Sequence::of(...$refl->getAttributes(Handles::class))
            ->map(static fn($attribute) => $attribute->newInstance())
            ->flatMap(static fn($handles) => $handles->messages());

        /** @psalm-suppress InvalidArgument Forced to lose type precision due to genericity of the Map */
        return new self(
            $this->os,
            $this->adapter,
            ($this->factories)($class, $factory),
            $this->messages->append($messages),
            $this->terminationGrace,
        );
    }

    /**
     * Messages not being declared here will be silently ignored at runtime.
     *
     * @psalm-mutation-free
     * @no-named-arguments
     *
     * @param class-string<Message> $message
     * @param class-string<Message> $messages
     */
    public function handle(string $message, string ...$messages): self
    {
        return new self(
            $this->os,
            $this->adapter,
            $this->factories,
            $this->messages->append(Sequence::of($message, ...$messages)),
            $this->terminationGrace,
        );
    }

    /**
     * @template I of Message
     * @template A of ?Message
     * @template T of Actor<I, A>
     *
     * @param class-string<T> $root
     * @param A $argument
     *
     * @return Maybe<SideEffect>
     */
    public function run(string $root, ?Message $argument = null): Maybe
    {
        $message = Init::root($root, $argument)
            ->normalize()
            ->serialize();

        return $this
            ->adapter
            ->mailboxes()
            ->root($this->os)
            ->flatMap(
                fn($mailbox) => $this
                    ->adapter
                    ->scheduled()
                    ->push(
                        $this->os,
                        Name::root(),
                    )
                    ->flatMap(static fn() => $mailbox->push(Sequence::of($message))),
            )
            ->map($this->loop(...));
    }

    private function loop(): SideEffect
    {
        $loop = Forerunner::of($this->os);

        return $loop(
            new SideEffect,
            Supervisor::of(
                $this->adapter,
                Factories::of($this->factories),
                Denormalize::of(
                    Init::class,
                    Tell::class,
                    Message\Parent\Failure::class,
                    Message\Child\Failure::class,
                    Message\Child\Termination::class,
                    ...$this->messages->toList(),
                ),
                $this->terminationGrace,
            ),
        );
    }
}
