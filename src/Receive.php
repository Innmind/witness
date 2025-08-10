<?php
declare(strict_types = 1);

namespace Innmind\Actors;

use Innmind\Actors\{
    Actor\Address,
    Receive\Continuation,
    Signal\PostStop,
    Signal\Child,
};

final class Receive
{
    /** @var ?array{Message, Address} */
    private ?array $message;
    private Child\Failure|PostStop|Child\Termination|null $signal;
    /** @var callable(Continuation): Continuation */
    private $handle;

    /**
     * @param ?array{Message, Address} $message
     * @param callable(Continuation): Continuation $handle
     */
    private function __construct(
        ?array $message,
        Child\Failure|PostStop|Child\Termination|null $signal,
        callable $handle,
    ) {
        $this->message = $message;
        $this->signal = $signal;
        $this->handle = $handle;
    }

    public static function message(Message $message, Address $sender): self
    {
        if ($message instanceof Message\Child\Failure) {
            return self::signal(Child\Failure::of(
                $sender,
                $message->class(),
                $message->code(),
                $message->message(),
            ));
        }

        return new self(
            [$message, $sender],
            null,
            static fn(Continuation $continuation) => $continuation,
        );
    }

    public static function signal(Child\Failure|PostStop|Child\Termination $signal): self
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
     * @param callable(Address, class-string<\Throwable>, int|string, string, Continuation):Continuation $handle
     */
    public function onChildFailure(callable $handle): self
    {
        if ($this->signal instanceof Child\Failure) {
            $signal = $this->signal;

            return new self(
                $this->message,
                $this->signal,
                static fn(Continuation $continuation) => $handle(
                    $signal->child(),
                    $signal->class(),
                    $signal->code(),
                    $signal->message(),
                    $continuation,
                ),
            );
        }

        return $this;
    }

    /**
     * @psalm-mutation-free
     *
     * @param callable(Address\Name, Continuation): Continuation $handle
     */
    public function onChildTermination(callable $handle): self
    {
        if ($this->signal instanceof Child\Termination) {
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
     * @param callable(): void $handle
     */
    public function onPostStop(callable $handle): self
    {
        if ($this->signal instanceof PostStop) {
            return new self(
                $this->message,
                $this->signal,
                static function(Continuation $continuation) use ($handle): Continuation {
                    $handle();

                    return $continuation->stop();
                },
            );
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
