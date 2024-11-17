<?php
declare(strict_types = 1);

namespace Innmind\Witness\Adapter\InMemory;

use Innmind\Witness\{
    Adapter\Scheduled as ScheduledInterface,
    Actor\Address\Name,
};
use Innmind\OperatingSystem\OperatingSystem;
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

final class Scheduled implements ScheduledInterface
{
    /**
     * @param Sequence<Name> $actors
     */
    private function __construct(
        private Sequence $actors,
        private Writable $send,
        private Readable $receive,
        private bool $valid,
    ) {
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        $pairs = @\stream_socket_pair(
            \STREAM_PF_UNIX,
            \STREAM_SOCK_STREAM,
            \STREAM_IPPROTO_IP,
        );

        if ($pairs === false) {
            throw new \RuntimeException('Unable to create a pair of sockets to notify scheduled actors');
        }

        return new self(
            Sequence::of(),
            Writable\Stream::of($pairs[0]),
            Readable\Stream::of($pairs[1]),
            true,
        );
    }

    public function push(OperatingSystem $os, Name $address): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<SideEffect> */
            return Maybe::nothing();
        }

        $this->actors = ($this->actors)($address);
        $watch = $os
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

    public function pull(OperatingSystem $os): Maybe
    {
        if (!$this->valid) {
            /** @var Maybe<Name> */
            return Maybe::nothing();
        }

        $watch = $os
            ->sockets()
            ->watch()
            ->forRead($this->receive);

        return $watch()
            ->toSequence()
            ->flatMap(static fn($ready) => $ready->toRead()->unsorted())
            ->first()
            ->flatMap(fn() => $this->actors->first())
            ->map(function($actor) {
                $this->actors = $this->actors->drop(1);

                return $actor;
            });
    }

    /**
     * @internal
     */
    public function terminate(): void
    {
        $this->actors = $this->actors->clear();
        $this->send->close()->memoize();
        $this->receive->close()->memoize();
        $this->valid = false;
    }
}
