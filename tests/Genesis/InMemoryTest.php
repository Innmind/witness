<?php
declare(strict_types = 1);

namespace Tests\Innmind\Witness\Genesis;

use Innmind\Witness\{
    Genesis\InMemory,
    Genesis,
    Actor,
    Message,
    Signal\PostStop,
    Exception\Stop,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class InMemoryTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Genesis::class, new InMemory);
    }

    public function testReturnWhenRunningWithNoActors()
    {
        $this->assertNull((new InMemory)->run());
    }

    public function testReturnWhenRunningWithoutSpawningRootActor()
    {
        $actor = $this->createMock(Actor::class);

        $genesis = (new InMemory)->actor(\get_class($actor), static fn() => $actor);

        $this->assertNull($genesis->run());
    }

    public function testStopSystemWhenRootActorIsKilled()
    {
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);
        $actor = $this->createMock(Actor::class);
        $actor
            ->expects($this->exactly(3))
            ->method('__invoke')
            ->withConsecutive(
                [$message1],
                [$message2],
                [new PostStop],
            )
            ->will($this->onConsecutiveCalls(
                null,
                $this->throwException(new Stop),
                null,
            ));

        $genesis = (new InMemory)->actor(\get_class($actor), static fn() => $actor);
        $root = $genesis->spawn(\get_class($actor));
        $root($message1);
        $root($message2);

        $this->assertNull($genesis->run());
    }
}
