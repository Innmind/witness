<?php
declare(strict_types = 1);

namespace Tests\Innmind\Witness\Genesis\InMemory;

use Innmind\Witness\{
    Genesis\InMemory\Children,
    Actor\Mailbox,
    Actor\Mailbox\Address,
};
use Innmind\Immutable\Set as ISet;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class ChildrenTest extends TestCase
{
    use BlackBox;

    public function testEmptyByDefault()
    {
        $children = new Children;

        $this->assertTrue($children->empty());
    }

    public function testNoLongerEmptyWhenRegisteringAChild()
    {
        $children = new Children;
        $children->register($this->createMock(Mailbox::class));

        $this->assertFalse($children->empty());
    }

    public function testRegisteringAndUnregisteringIsAnIdentityFunction()
    {
        $children = new Children;
        $child = $this->createMock(Mailbox::class);
        $child
            ->method('address')
            ->willReturn($this->createMock(Address::class));
        $children->register($child);
        $children->unregister($child->address());

        $this->assertTrue($children->empty());
    }

    public function testAddresses()
    {
        $this
            ->forAll(Set\Sequence::of(
                Set\FromGenerator::of(function() {
                    while (true) {
                        $mailbox = $this->createMock(Mailbox::class);
                        $mailbox
                            ->method('address')
                            ->willReturn($this->createMock(Address::class));

                        yield $mailbox;
                    }
                }),
                Set\Integers::between(0, 5),
            ))
            ->then(function($mailboxes) {
                $children = new Children;

                foreach ($mailboxes as $mailbox) {
                    $children->register($mailbox);
                }

                $addresses = $children->addresses();
                $this->assertInstanceOf(ISet::class, $addresses);
                $this->assertSame(Address::class, $addresses->type());
                $this->assertCount(\count($mailboxes), $addresses);

                foreach ($mailboxes as $mailbox) {
                    $this->assertTrue($addresses->contains($mailbox->address()));
                }
            });
    }
}
