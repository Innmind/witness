<?php
declare(strict_types = 1);

namespace Innmind\Actors;

use Innmind\Actors\Message\Payload;
use Innmind\Immutable\Maybe;

final class Denormalize
{
    private function __construct(
        /** @var list<class-string<Message>> */
        private array $classes,
    ) {
    }

    /**
     * @return Maybe<Message>
     */
    public function __invoke(Payload $payload): Maybe
    {
        /** @var Maybe<Message> */
        $message = Maybe::nothing();

        foreach ($this->classes as $class) {
            $message = $message->otherwise(
                fn() => $class::denormalize($this, $payload),
            );
        }

        return $message;
    }

    /**
     * @no-named-arguments
     *
     * @param class-string<Message> $classes
     */
    public static function of(string ...$classes): self
    {
        return new self($classes);
    }
}
