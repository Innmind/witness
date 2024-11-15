<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message;

use Innmind\Witness\{
    Message,
    Actor\Address\Name,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Tell implements Message
{
    private function __construct(
        private Name $sender,
        private Message $message,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(Name $sender, Message $message): self
    {
        return new self($sender, $message);
    }

    public function sender(): Name
    {
        return $this->sender;
    }

    public function message(): Message
    {
        return $this->message;
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => 'innmind-witness-tell',
            'sender' => $this->sender->toString(),
            'message' => $this->message->normalize(),
        ]);
    }
}
