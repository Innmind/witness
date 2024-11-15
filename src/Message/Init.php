<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message;

use Innmind\Witness\{
    Message,
    Actor,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Init implements Message
{
    /**
     * @param class-string<Actor> $actor
     */
    private function __construct(
        private string $actor,
        private Message $argument,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param class-string<Actor> $actor
     */
    public static function of(string $actor, Message $argument): self
    {
        return new self($actor, $argument);
    }

    /**
     * @return class-string<Actor>
     */
    public function actor(): string
    {
        return $this->actor;
    }

    public function argument(): Message
    {
        return $this->argument;
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => 'innmind-witness-actor-init',
            'actor' => $this->actor,
            'argument' => $this->argument->normalize(),
        ]);
    }
}
