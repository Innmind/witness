<?php
declare(strict_types = 1);

namespace Innmind\Actors\Message;

use Innmind\Actors\{
    Actor\Address\Name,
    Message,
    Denormalize,
};
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};
use Ramsey\Uuid\Uuid;

/**
 * @psalm-immutable
 * @internal
 */
final class Tell implements Message
{
    private const KEY = 'innmind-witness-tell';

    private function __construct(
        private Name $sender,
        private Message $message,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(Name $sender, Message $message): self
    {
        return new self($sender, $message);
    }

    /**
     * @psalm-pure
     */
    public static function denormalize(
        Denormalize $denormalize,
        Payload $payload,
    ): Maybe {
        return Maybe::just($payload->unwrap())
            ->keep(Instance::of(Payload\Shape::class))
            ->map(static fn($shape) => $shape->unwrap())
            ->flatMap(
                static fn($shape) => Maybe::all(
                    $shape
                        ->get('id')
                        ->filter(static fn($id) => $id === self::KEY),
                    $shape
                        ->get('sender')
                        ->keep(Is::string()->asPredicate())
                        ->flatMap(
                            static fn($string) => Maybe::just($string)
                                ->filter(Uuid::isValid(...))
                                ->map(Uuid::fromString(...))
                                ->map(Name::of(...))
                                ->otherwise(static fn() => Maybe::of(match ($string) {
                                    'root' => Name::root(),
                                    default => null,
                                })),
                        ),
                    $shape
                        ->get('message')
                        ->keep(Instance::of(Payload::class))
                        ->flatMap($denormalize),
                )->map(static fn($_, Name $sender, Message $message) => new self(
                    $sender,
                    $message,
                )),
            );
    }

    public function sender(): Name
    {
        return $this->sender;
    }

    public function message(): Message
    {
        return $this->message;
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => self::KEY,
            'sender' => $this->sender->toString(),
            'message' => $this->message->normalize(),
        ]);
    }
}
