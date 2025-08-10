<?php
declare(strict_types = 1);

require __DIR__.'/../vendor/autoload.php';

use Innmind\Actors\{
    System,
    Adapter\InMemory,
    Actor,
    Actor\Address,
    Handles,
    Message,
    Message\Payload,
    Receive,
    Receive\Continuation,
    Spawn,
    Denormalize,
};
use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\TimeContinuum\Earth\Period\Second;
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
    Sequence,
};

enum Tld implements Message {
    case fr;
    case org;

    /**
     * @psalm-pure
     */
    public static function denormalize(
        Denormalize $denormalize,
        Payload $payload,
    ): Maybe {
        return Maybe::just($payload->unwrap())
            ->keep(Instance::of(Payload\Shape::class))
            ->map(static fn($shape) => $shape->unwrap())
            ->flatMap(
                static fn($shape) => Maybe::all(
                    $shape
                        ->get('id')
                        ->filter(static fn($id) => $id === self::class),
                    $shape
                        ->get('name')
                        ->keep(Is::string()->asPredicate()),
                )->map(static fn($_, string $name) => match ($name) {
                    'fr' => self::fr,
                    'org' => self::org,
                }),
            );
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => self::class,
            'name' => $this->name,
        ]);
    }
}

/**
 * @psalm-immutable
 */
final class Url implements Message
{
    private function __construct(
        private string $value,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(string $value): self
    {
        return new self($value);
    }

    /**
     * @psalm-pure
     */
    public static function denormalize(
        Denormalize $denormalize,
        Payload $payload,
    ): Maybe {
        return Maybe::just($payload->unwrap())
            ->keep(Instance::of(Payload\Shape::class))
            ->map(static fn($shape) => $shape->unwrap())
            ->flatMap(
                static fn($shape) => Maybe::all(
                    $shape
                        ->get('id')
                        ->filter(static fn($id) => $id === self::class),
                    $shape
                        ->get('value')
                        ->keep(Is::string()->asPredicate()),
                )->map(static fn($_, string $value) => new self($value)),
            );
    }

    public function value(): string
    {
        return $this->value;
    }

    public function normalize(): Payload
    {
        return Payload::of([
            'id' => self::class,
            'value' => $this->value,
        ]);
    }

    public function is(Tld $tld): bool
    {
        return \str_contains($this->value, '.'.$tld->name);
    }
}

/**
 * @return Sequence<Url>
 */
function crawl(OperatingSystem $os, Url $url): Sequence
{
    $second = \rand(1, 5);
    \printf("Sleeping for %s seconds\n", $second);
    $os->process()->halt(Second::of($second));
    \printf("Done sleeping\n");

    $urls = match ($url->value()) {
        'https://wikipedia.org' => ['https://en.wikipedia.org/', 'https://wikipedia.fr/'],
        'https://en.wikipedia.org/' => ['https://en.wikipedia.org/wiki/PHP', 'https://example.org/'],
        'https://wikipedia.fr/' => ['https://wikipedia.fr/wiki/PHP', 'https://gouv.fr'],
        default => [],
    };

    return Sequence::of(...$urls)->map(Url::of(...));
}

final class Crawler implements Actor
{
    private Address $fr;
    private Address $org;

    public function __construct(Spawn $spawn, Url $url)
    {
        $this->fr = $spawn(ChildCrawler::class, Tld::fr)
            ->memoize()
            ->match(
                static fn($address) => $address,
                static fn() => throw new \Exception,
            );
        $this->org = $spawn(ChildCrawler::class, Tld::org)
            ->memoize()
            ->match(
                static fn($address) => $address,
                static fn() => throw new \Exception,
            );

        if ($url->is(Tld::fr)) {
            ($this->fr)(Sequence::of($url))->memoize();
        } else if ($url->is(Tld::org)) {
            ($this->org)(Sequence::of($url))->memoize();
        }
    }

    public function __invoke(Receive $receive): Receive
    {
        return $receive->on(
            Url::class,
            function(Url $url, Address $sender, Continuation $continuation) {
                \printf(
                    "Root actor handling %s\n",
                    $url->value(),
                );

                if ($url->is(Tld::fr)) {
                    ($this->fr)(Sequence::of($url))->memoize();
                } else if ($url->is(Tld::org)) {
                    ($this->org)(Sequence::of($url))->memoize();
                }

                return $continuation->continue();
            },
        );
    }
}

final class ChildCrawler implements Actor
{
    public function __construct(
        private OperatingSystem $os,
        private Tld $tld,
    ) {
    }

    public function __invoke(Receive $receive): Receive
    {
        return $receive->on(
            Url::class,
            function(Url $url, Address $sender, Continuation $continuation) {
                \printf(
                    "Actor %s trying to handle %s\n",
                    $this->tld->name,
                    $url->value(),
                );

                if (!$url->is($this->tld)) {
                    \printf(
                        "Actor %s sending back %s\n",
                        $this->tld->name,
                        $url->value(),
                    );

                    $sender(Sequence::of($url))->memoize();

                    return $continuation->continue();
                }

                \printf(
                    "Actor %s crawling %s\n",
                    $this->tld->name,
                    $url->value(),
                );

                $urls = crawl($this->os, $url);
                $sender($urls)->memoize();

                return $continuation->continue();
            },
        );
    }
}

System::of(
    Factory::build(),
    InMemory::new(),
)
    ->handle(Url::class, Tld::class)
    ->actor(
        Crawler::class,
        static fn($_, Url $url, Spawn $spawn) => new Crawler($spawn, $url),
    )
    ->actor(
        ChildCrawler::class,
        static fn(OperatingSystem $os, Tld $tld) => new ChildCrawler($os, $tld),
    )
    ->run(Crawler::class, Url::of('https://wikipedia.org'));
