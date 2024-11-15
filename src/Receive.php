<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\{
    Actor\Address,
    Receive\Continuation,
    Signal\ChildFailed,
    Signal\PostStop,
    Signal\PreRestart,
    Signal\Terminated,
};

final class Receive
{
    private function __construct()
    {
    }

    public static function message(Message $message): self
    {
        return new self;
    }

    public static function signal(ChildFailed|PostStop|PreRestart|Terminated $signal): self
    {
        return new self;
    }

    /**
     * @template M of Message
     * @template I of Message
     * @template A of Message
     *
     * @param class-string<M> $class
     * @param callable(M, Address<I, Actor<I, A>>, Continuation): Continuation $handle
     */
    public function on(string $class, callable $handle): self
    {
        return new self;
    }

    /**
     * @template M of Message
     * @template A of Message
     *
     * @param callable(Address<M, Actor<M, A>>, Continuation):Continuation $handle
     */
    public function onChildFailure(callable $handle): self
    {
        return new self;
    }

    /**
     * @template M of Message
     * @template A of Message
     *
     * @param callable(Address<M, Actor<M, A>>, Continuation): Continuation $handle
     */
    public function onChildTerminated(callable $handle): self
    {
        return new self;
    }

    /**
     * @param callable(Continuation): Continuation $handle
     */
    public function onPostStop(callable $handle): self
    {
        return new self;
    }

    /**
     * @param callable(Continuation): Continuation $handle
     */
    public function onPreRestart(callable $handle): self
    {
        return new self;
    }

    /**
     * @internal
     */
    public function handle(Continuation $continuation): Continuation
    {
        return $continuation;
    }
}
