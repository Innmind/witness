<?php
declare(strict_types = 1);

namespace Innmind\Actors\Actor;

use Innmind\Actors\{
    Message,
    Actor\Mailbox\Address,
    Actor\Mailbox\Consume,
};
use Innmind\Immutable\Maybe;

/**
 * @internal
 */
interface Mailbox
{
    /**
     * @return Address<Message>
     */
    public function address(): Address;

    /**
     * @return Maybe<self> Whether the mailbox still exists after the execution or not
     */
    public function consume(Consume $continue): Maybe;

    /**
     * Stop the actor associate with this mailbox
     *
     * Doesn't mean it will be stopped imediately
     */
    public function stop(): void;
}
