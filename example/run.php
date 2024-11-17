<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\Witness\{
    Genesis,
    Adapter\InMemory,
    Actor,
    Handles,
    Message\Str,
    Receive,
};
use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\TimeContinuum\Earth\Period\Second;
use Innmind\Immutable\Sequence;

#[Handles(Str::class)]
final class Side implements Actor
{
    public function __construct(
        private OperatingSystem $os,
        private string $side,
    ) {
    }

    public function __invoke(Receive $receive): Receive
    {
        return $receive->on(
            Str::class,
            function($message, $sender, $continuation) {
                echo $message->message()."\n";

                $this->os->process()->halt(Second::of(1));
                $sender(Sequence::of(Str::of($this->side)));

                return $continuation->continue();
            },
        );
    }
}

Genesis::of(
    Factory::build(),
    InMemory::new(),
)
    ->actor(Side::class, static function($os, $init, $spawn) {
        if ($init->message() === 'ping') {
            $spawn(Side::class, Str::of('pong'))
                ->flatMap(static fn($pong) => $pong(Sequence::of(Str::of('ping'))))
                ->memoize();
        }

        return new Side($os, $init->message());
    })
    ->run(Side::class, Str::of('ping'));
