<?php
declare(strict_types = 1);

namespace Tests\Innmind\Witness\Actor\Mailbox;

use Innmind\Witness\{
    Actor\Mailbox\InMemory,
    Actor\Mailbox,
    Actor\Mailbox\Address,
    Actor\Mailbox\Consume\Always,
    Genesis\InMemory\Children,
    Actor,
    Message,
    Signal\PostStop,
    Signal\Terminated,
    Signal\ChildFailed,
    Signal\PreRestart,
    Exception\Stop,
};
use PHPUnit\Framework\TestCase;

class InMemoryTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Mailbox::class,
            new InMemory(
                static fn() => null,
                static fn() => null,
                new Children,
            ),
        );
    }

    public function testMailboxAddressDoesntChange()
    {
        $mailbox = new InMemory(
            static fn() => null,
            static fn() => null,
            new Children,
        );

        $this->assertSame($mailbox->address(), $mailbox->address());
        $this->assertSame($mailbox->address()->toString(), $mailbox->address()->toString());
    }

    public function testActorIsNotStartedWhenConsumingButNoMessages()
    {
        $started = false;
        $mailbox = new InMemory(
            static function() use (&$started) {
                $started = true;
            },
            static fn() => null,
            new Children,
        );

        $mailbox->consume(new Always);
        $this->assertFalse($started);
    }

    public function testReturnMailboxWhenConsumingButNotMessageAndNoChild()
    {
        $mailbox = new InMemory(
            static fn() => null,
            static fn() => null,
            new Children,
        );

        $this->assertTrue($mailbox->consume(new Always)->match(
            static fn() => true,
            static fn() => false,
        ));
    }

    public function testStartActorWhenConsumingFirstMessage()
    {
        $message = $this->createMock(Message::class);
        $actor = null;
        $mailbox = new InMemory(
            function() use (&$actor, $message) {
                $actor = $this->createMock(Actor::class);
                $actor
                    ->expects($this->once())
                    ->method('__invoke')
                    ->with($message);

                return $actor;
            },
            static fn() => null,
            new Children,
        );

        $this->assertNull($mailbox->address()($message));
        $this->assertNull($actor);
        $mailbox->consume(new Always);
        $this->assertInstanceOf(Actor::class, $actor);
    }

    public function testDoesntStartActorWhenStoppingTheMailbox()
    {
        $started = false;
        $mailbox = new InMemory(
            static function() use (&$started) {
                $started = true;
            },
            static fn() => null,
            new Children,
        );

        $this->assertNull($mailbox->stop());
        $this->assertFalse($mailbox->consume(new Always)->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($started);
    }

    public function testStopTriggersAStopOfTheChildren()
    {
        $children = new Children;
        $child = $this->createMock(Mailbox::class);
        $mailbox = new InMemory(
            static fn() => null,
            static fn() => null,
            $children,
        );

        $children->register($child);
        $child
            ->expects($this->once())
            ->method('stop');

        $mailbox->stop();
    }

    public function testDiscardMessagesAfterAskingForAStop()
    {
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);
        $signals = 0;

        $mailbox = new InMemory(
            function() use ($message1) {
                $actor = $this->createMock(Actor::class);
                $actor
                    ->expects($this->exactly(2))
                    ->method('__invoke')
                    ->withConsecutive(
                        [$message1],
                        [new PostStop],
                    )
                    ->will($this->onConsecutiveCalls(
                        $this->throwException(new Stop),
                        null,
                    ));

                return $actor;
            },
            function($signal) use (&$signals) {
                ++$signals;
                $this->assertInstanceOf(Terminated::class, $signal);
            },
            new Children,
        );
        $mailbox->address()($message1);
        $mailbox->address()($message2);
        // we need 2 consumes because the firs one will consume the first message
        // and trap the second one as the first asked for a top, the second call
        // knows we asked for a stop and modify the queue to only provide a
        // PostStop signal that will trigger the Terminated signal for the parent
        $mailbox->consume(new Always);
        $mailbox->consume(new Always);

        $this->assertSame(1, $signals);
    }

    public function testTerminatedSignalAutomaticallyRemovesTheChildFromTheCollection()
    {
        $children = new Children;
        $child = $this->createMock(Mailbox::class);
        $child
            ->method('address')
            ->willReturn($address = $this->createMock(Address::class));
        $signal = Terminated::of($address);
        $children->register($child);
        $actor = $this->createMock(Actor::class);
        $actor
            ->expects($this->once())
            ->method('__invoke')
            ->with($signal);

        $mailbox = new InMemory(
            static fn() => $actor,
            static fn() => null,
            $children,
        );
        $mailbox->address()->signal($signal);
        $mailbox->consume(new Always);

        $this->assertTrue($children->empty());
    }

    public function testActorIsRestartedAfterAnUnhandledException()
    {
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);

        $actor = $this->createMock(Actor::class);
        $actor
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$message1],
                [new PreRestart],
            )
            ->will($this->onConsecutiveCalls(
                $this->throwException(new \Exception),
                null,
            ));
        $nextActor = $this->createMock(Actor::class);
        $nextActor
            ->expects($this->once())
            ->method('__invoke')
            ->with($message2);
        $started = 0;
        $signals = 0;
        $mailbox = new InMemory(
            static function() use (&$started, &$actor, $nextActor) {
                ++$started;
                $return = $actor;
                $actor = $nextActor;

                return $return;
            },
            function($signal) use (&$signals) {
                ++$signals;
                $this->assertInstanceOf(ChildFailed::class, $signal);
            },
            new Children,
        );
        $mailbox->address()($message1);
        $mailbox->address()($message2);
        $mailbox->consume(new Always);

        $this->assertSame(2, $started);
        $this->assertSame(1, $signals);
    }

    public function testDiscardErrorThrownOnPreRestart()
    {
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);

        $actor = $this->createMock(Actor::class);
        $actor
            ->expects($this->exactly(2))
            ->method('__invoke')
            ->withConsecutive(
                [$message1],
                [new PreRestart],
            )
            ->will($this->onConsecutiveCalls(
                $this->throwException(new \Exception),
                $this->throwException(new \Exception),
            ));
        $nextActor = $this->createMock(Actor::class);
        $nextActor
            ->expects($this->once())
            ->method('__invoke')
            ->with($message2);
        $started = 0;
        $signals = 0;
        $mailbox = new InMemory(
            static function() use (&$started, &$actor, $nextActor) {
                ++$started;
                $return = $actor;
                $actor = $nextActor;

                return $return;
            },
            function($signal) use (&$signals) {
                ++$signals;
                $this->assertInstanceOf(ChildFailed::class, $signal);
            },
            new Children,
        );
        $mailbox->address()($message1);
        $mailbox->address()($message2);
        $mailbox->consume(new Always);

        $this->assertSame(2, $started);
        $this->assertSame(1, $signals);
    }

    public function testDiscardErrorThrownOnPostStop()
    {
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);
        $signals = 0;

        $mailbox = new InMemory(
            function() use ($message1) {
                $actor = $this->createMock(Actor::class);
                $actor
                    ->expects($this->exactly(2))
                    ->method('__invoke')
                    ->withConsecutive(
                        [$message1],
                        [new PostStop],
                    )
                    ->will($this->onConsecutiveCalls(
                        $this->throwException(new Stop),
                        $this->throwException(new \Exception),
                    ));

                return $actor;
            },
            function($signal) use (&$signals) {
                ++$signals;
                $this->assertInstanceOf(Terminated::class, $signal);
            },
            new Children,
        );
        $mailbox->address()($message1);
        $mailbox->address()($message2);
        // we need 2 consumes because the firs one will consume the first message
        // and trap the second one as the first asked for a top, the second call
        // knows we asked for a stop and modify the queue to only provide a
        // PostStop signal that will trigger the Terminated signal for the parent
        $mailbox->consume(new Always);
        $mailbox->consume(new Always);

        $this->assertSame(1, $signals);
    }
}
