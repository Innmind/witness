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
    /** @var ?array{Message, Address} */
    private ?array $message;
    private ChildFailed|PostStop|PreRestart|Terminated|null $signal;
    /** @var callable(Continuation): Continuation */
    private $handle;

    /**
     * @param ?array{Message, Address} $message
     * @param callable(Continuation): Continuation $handle
     */
    private function __construct(
        ?array $message,
        ChildFailed|PostStop|PreRestart|Terminated|null $signal,
        callable $handle,
    ) {
        $this->message = $message;
        $this->signal = $signal;
        $this->handle = $handle;
    }

    public static function message(Message $message, Address $sender): self
    {
        if ($message instanceof Message\Signal) {
            return self::signal($message->signal($sender));
        }

        return new self(
            [$message, $sender],
            null,
            static fn(Continuation $continuation) => $continuation,
        );
    }

    public static function signal(ChildFailed|PostStop|PreRestart|Terminated $signal): self
    {
        return new self(
            null,
            $signal,
            static fn(Continuation $continuation) => $continuation,
        );
    }

    /**
     * @psalm-mutation-free
     * @template M of Message
     *
     * @param class-string<M> $class
     * @param callable(M, Address, Continuation): Continuation $handle
     */
    public function on(string $class, callable $handle): self
    {
        if (\is_null($this->message)) {
            return $this;
        }

        [$message, $sender] = $this->message;

        if ($message instanceof $class) {
            return new self(
                $this->message,
                $this->signal,
                static fn(Continuation $continuation) => $handle(
                    $message,
                    $sender,
                    $continuation,
                ),
            );
        }

        return $this;
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(Address, Continuation):Continuation $handle
     */
    public function onChildFailure(callable $handle): self
    {
        if ($this->signal instanceof ChildFailed) {
            $signal = $this->signal;

            return new self(
                $this->message,
                $this->signal,
                static fn(Continuation $continuation) => $handle(
                    $signal->child(),
                    $continuation,
                ),
            );
        }

        return $this;
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(Address, Continuation): Continuation $handle
     */
    public function onChildTerminated(callable $handle): self
    {
        if ($this->signal instanceof Terminated) {
            $signal = $this->signal;

            return new self(
                $this->message,
                $this->signal,
                static fn(Continuation $continuation) => $handle(
                    $signal->child(),
                    $continuation,
                ),
            );
        }

        return $this;
    }

    /**
     * @psalm-mutation-free
     * @param callable(Continuation): Continuation $handle
     */
    public function onPostStop(callable $handle): self
    {
        if ($this->signal instanceof PostStop) {
            return new self($this->message, $this->signal, $handle);
        }

        return $this;
    }

    /**
     * @psalm-mutation-free
     * @param callable(Continuation): Continuation $handle
     */
    public function onPreRestart(callable $handle): self
    {
        if ($this->signal instanceof PreRestart) {
            return new self($this->message, $this->signal, $handle);
        }

        return $this;
    }

    /**
     * @internal
     */
    public function handle(Continuation $continuation): Continuation
    {
        return ($this->handle)($continuation);
    }
}
