<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Witness\{
    Actor\Mailbox as MailboxInterface,
    Actor\Address,
    Actor\Address\Name,
    Message\Payload\Serialized,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\Period;
use Innmind\Immutable\{
    Maybe,
    Sequence,
    SideEffect,
};

final class Mailbox implements MailboxInterface
{
    /**
     * @param \SplQueue<Serialized> $messages
     */
    private function __construct(
        private OperatingSystem $os,
        private Name $name,
        private \SplQueue $messages,
        private bool $valid,
    ) {
    }

    /**
     * @internal
     */
    public static function of(
        OperatingSystem $os,
        Name $name,
    ): self {
        /** @var \SplQueue<Serialized> */
        $messages = new \SplQueue;
        $messages->setIteratorMode(\SplQueue::IT_MODE_FIFO | \SplQueue::IT_MODE_DELETE);

        return new self($os, $name, $messages, true);
    }

    public function address(Name $sender): Address
    {
        return Address::of(
            $this->name,
            $this,
            $sender,
        );
    }

    public function push(Sequence $messages): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        // todo ping on a local pair of sockets
        $_ = $messages->foreach(fn($message) => $this->messages->enqueue($message));

        return Maybe::just(new SideEffect);
    }

    public function pull(?Period $max = null): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<Serialized> */
            return Maybe::nothing();
        }

        // todo wait ping on the local pair of sockets if no message already in
        // the queue to avoid infinite polling

        foreach ($this->messages as $message) {
            return Maybe::just($message);
        }

        /** @var Maybe<Serialized> */
        return Maybe::nothing();
    }

    /**
     * @internal
     */
    public function swap(OperatingSystem $os): self
    {
        return new self(
            $os,
            $this->name,
            $this->messages,
            $this->valid,
        );
    }

    /**
     * @internal
     */
    public function invalidate(): void
    {
        $this->valid = false;
    }
}
