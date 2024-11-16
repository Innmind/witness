<?php
declare(strict_types = 1);

namespace Innmind\Witness\Signal;

/**
 * @psalm-immutable
 * @internal
 */
final class Recover
{
    private function __construct(
        private \Throwable $error,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(
        \Throwable $error,
    ): self {
        return new self($error);
    }

    public function error(): \Throwable
    {
        return $this->error;
    }
}
