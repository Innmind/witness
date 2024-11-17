<?php
declare(strict_types = 1);

namespace Innmind\Witness;

use Innmind\Immutable\Sequence;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Handles
{
    /** @var Sequence<class-string<Message>> */
    private Sequence $messages;

    /**
     * @param class-string<Message> $class
     * @param class-string<Message> $classes
     */
    public function __construct(string $class, string ...$classes)
    {
        $this->messages = Sequence::of($class, ...$classes);
    }

    /**
     * @internal
     *
     * @return Sequence<class-string<Message>>
     */
    public function messages(): Sequence
    {
        return $this->messages;
    }
}
