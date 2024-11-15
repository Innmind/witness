<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Witness\Message\Payload;
use Innmind\Immutable\Maybe;

final class Messages
{
    private function __construct(
        /** @var list<callable(Payload): Maybe<Message>> */
        private array $denormalizers,
    ) {
    }

    /**
     * @no-named-arguments
     *
     * @param callable(Payload): Maybe<Message> $denormalizers
     */
    public static function of(callable ...$denormalizers): self
    {
        return new self($denormalizers);
    }

    public function normalize(Message $message): Payload
    {
        return $message->normalize();
    }

    public function denormalize(Payload $payload): Message
    {
        /** @var Maybe<Message> */
        $message = Maybe::nothing();

        foreach ($this->denormalizers as $denormalize) {
            $message = $message->otherwise(
                static fn() => $denormalize($payload),
            );
        }

        return $message->match(
            static fn($message) => $message,
            static fn() => throw new \LogicException('Unable to denormalize a message payload'),
        );
    }
}
