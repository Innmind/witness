<?php
declare(strict_types = 1);

namespace Innmind\Witness\Signal;

use Innmind\Witness\Actor\Address;

/**
 * @psalm-immutable
 * @internal
 */
final class ChildFailed
{
    /**
     * @param class-string<\Throwable> $class
     */
    private function __construct(
        private Address $child,
        private string $class,
        private int|string $code,
        private string $message,
    ) {
    }

    /**
     * @psalm-pure
     *
     * @param class-string<\Throwable> $class
     */
    public static function of(
        Address $child,
        string $class,
        int|string $code,
        string $message,
    ): self {
        return new self($child, $class, $code, $message);
    }

    public function child(): Address
    {
        return $this->child;
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
}
