<?php
declare(strict_types = 1);

namespace Innmind\Witness\Actor\Mailbox\Address;

use Innmind\Witness\{
    Actor\Mailbox,
    Actor\Mailbox\Address,
    Message,
};

final class InMemory implements Address
{
    /** @var callable(Message): void */
    private $publish;

    /**
     * @param callable(Message): void $publish
     */
    public function __construct(callable $publish)
    {
        $this->publish = $publish;
    }

    public function __invoke(Message $message): void
    {
        ($this->publish)($message);
    }

    public function toString(): string
    {
        return \spl_object_hash($this);
    }
}
