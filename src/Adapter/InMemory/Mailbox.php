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
use Innmind\TimeContinuum\{
    Period,
    Earth\ElapsedPeriod,
};
use Innmind\Stream\{
    Readable,
    Writable,
};
use Innmind\Immutable\{
    Str,
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
        private Writable $send,
        private Readable $receive,
        private bool $valid,
    ) {
    }

    /**
     * @internal
     *
     * @return Maybe<self>
     */
    public static function of(
        OperatingSystem $os,
        Name $name,
    ): Maybe {
        /** @var \SplQueue<Serialized> */
        $messages = new \SplQueue;
        $messages->setIteratorMode(\SplQueue::IT_MODE_FIFO | \SplQueue::IT_MODE_DELETE);

        $pairs = @\stream_socket_pair(
            \STREAM_PF_UNIX,
            \STREAM_SOCK_STREAM,
            \STREAM_IPPROTO_IP,
        );

        if ($pairs === false) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self(
            $os,
            $name,
            $messages,
            Writable\Stream::of($pairs[0]),
            Readable\Stream::of($pairs[1]),
            true,
        ));
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

        $_ = $messages->foreach(fn($message) => $this->messages->enqueue($message));
        $watch = $this
            ->os
            ->sockets()
            ->watch()
            ->forWrite($this->send);
        $_ = $watch()
            ->toSequence()
            ->flatMap(static fn($ready) => $ready->toWrite()->unsorted())
            ->flatMap(
                static fn($send) => $send
                    ->write(Str::of('.'))
                    ->maybe()
                    ->toSequence(),
            )
            ->memoize();

        return Maybe::just(new SideEffect);
    }

    public function pull(?Period $max = null): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<Serialized> */
            return Maybe::nothing();
        }

        if (!$this->messages->isEmpty()) {
            return Maybe::just($this->messages->dequeue());
        }

        $watch = $this
            ->os
            ->sockets()
            ->watch(match ($max) {
                null => null,
                default => ElapsedPeriod::ofPeriod($max),
            })
            ->forRead($this->receive);

        return $watch()
            ->toSequence()
            ->flatMap(static fn($ready) => $ready->toRead()->unsorted())
            ->first()
            ->filter(fn() => !$this->messages->isEmpty())
            ->map(fn() => $this->messages->dequeue());
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
            $this->send,
            $this->receive,
            $this->valid,
        );
    }

    /**
     * @internal
     */
    public function invalidate(): void
    {
        $this->send->close()->memoize();
        $this->receive->close()->memoize();
        $this->valid = false;
    }
}
