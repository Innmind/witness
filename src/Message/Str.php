<?php
declare(strict_types = 1);

namespace Innmind\Actors\Message;

use Innmind\Actors\{
    Message,
    Denormalize,
};
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Str implements Message
{
    private const KEY = 'innmind-actors-message-str';

    private function __construct(
        private string $message,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(string $message): self
    {
        return new self($message);
    }

    /**
     * @psalm-pure
     */
    #[\Override]
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
                        ->get('message')
                        ->keep(Is::string()->asPredicate()),
                )->map(static fn($_, string $message) => new self($message)),
            );
    }

    public function message(): string
    {
        return $this->message;
    }

    #[\Override]
    public function normalize(): Payload
    {
        return Payload::of([
            'id' => self::KEY,
            'message' => $this->message,
        ]);
    }
}
