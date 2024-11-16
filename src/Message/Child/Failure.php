<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message\Child;

use Innmind\Witness\{
    Message,
    Message\Payload,
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
final class Failure implements Message
{
    private const KEY = 'innmind-witness-signal-child-failure';

    /**
     * @param class-string<\Throwable> $class
     */
    private function __construct(
        private string $class,
        private int|string $code,
        private string $message,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(\Throwable $e): self
    {
        return new self($e::class, $e->getCode(), $e->getMessage());
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
                        ->get('class')
                        ->keep(Is::string()->asPredicate()),
                    $shape
                        ->get('code')
                        ->keep(
                            Is::int()
                                ->or(Is::string())
                                ->asPredicate(),
                        ),
                    $shape
                        ->get('message')
                        ->keep(Is::string()->asPredicate()),
                )->map(
                    /** @psalm-suppress ArgumentTypeCoercion Because $class is not a class-string */
                    static fn(mixed $_, string $class, int $code, string $message) => new self(
                        $class,
                        $code,
                        $message,
                    ),
                ),
            );
    }

    /**
     * @return class-string<\Throwable>
     */
    public function class(): string
    {
        return $this->class;
    }

    public function code(): int|string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => self::KEY,
            'class' => $this->class,
            'code' => $this->code,
            'message' => $this->message,
        ]);
    }
}
