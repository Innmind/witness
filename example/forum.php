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
    Message\Str,
    Receive,
    Receive\Continuation,
    Spawn,
    Denormalize,
};
use Innmind\OperatingSystem\{
    Factory,
    OperatingSystem,
};
use Innmind\HttpTransport\FollowRedirections;
use Innmind\Http\{
    Request,
    Method,
    ProtocolVersion,
    Headers,
    Header\Header,
    Header\Value\Value,
};
use Innmind\TimeContinuum\Earth\Period\Second;
use Innmind\Url\Url as BaseUrl;
use Innmind\UrlResolver\UrlResolver;
use Innmind\Html\Element\A;
use Innmind\Html\Reader\Reader;
use Innmind\Xml\Node;
use Innmind\Validation\Is;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
    Sequence,
};

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
}

function gather(Node $node): Sequence
{
    if ($node instanceof A) {
        return Sequence::of($node->href());
    }

    return $node->children()->flatMap(gather(...));
}

/**
 * @return Sequence<Url>
 */
function crawl(OperatingSystem $os, Url $url): Sequence
{
    $resolve = UrlResolver::of('http', 'https');
    $http = FollowRedirections::of($os->remote()->http());

    $urls = $http(Request::of(
        BaseUrl::of($url->value()),
        Method::get,
        ProtocolVersion::v11,
        Headers::of(
            new Header('User-Agent', new Value('innmind/actor demo')),
        ),
    ))
        ->map(static fn($success) => $success->response()->body())
        ->maybe()
        ->flatMap(Reader::default())
        ->toSequence()
        ->flatMap(gather(...))
        ->map(static fn($dest) => $resolve(
            BaseUrl::of($url->value()),
            $dest,
        ))
        ->map(static fn($url) => Url::of($url->toString()));

    return $urls;
}

final class Crawler implements Actor
{
    /** @var array<string, Address> */
    private array $children;

    public function __construct(private Spawn $spawn, Url $url)
    {
        $this->forward($url);
    }

    public function __invoke(Receive $receive): Receive
    {
        return $receive->on(
            Url::class,
            function(Url $url, Address $sender, Continuation $continuation) {
                $this->forward($url);

                return $continuation->continue();
            },
        );
    }

    private function forward(Url $url): void
    {
        $host = BaseUrl::of($url->value())
            ->authority()
            ->host()
            ->toString();
        $parts = \explode('.', $host);
        $tld = \end($parts);
        $child = $this->children[$tld] ??= ($this->spawn)(
            ChildCrawler::class,
            Str::of($tld),
        )->match(
            static fn($address) => $address,
            static fn() => throw new \Exception,
        );

        $child(Sequence::of($url))->memoize();
    }
}

final class ChildCrawler implements Actor
{
    public function __construct(
        private OperatingSystem $os,
        private string $tld,
    ) {
    }

    public function __invoke(Receive $receive): Receive
    {
        return $receive->on(
            Url::class,
            function(Url $url, Address $sender, Continuation $continuation) {
                \printf(
                    "Actor %-5s crawling %s\n",
                    $this->tld,
                    $url->value(),
                );

                $urls = crawl($this->os, $url);
                $sender($urls)->memoize();

                \printf(
                    "Actor %-5s done\n",
                    $this->tld,
                    $url->value(),
                );

                return $continuation->continue();
            },
        );
    }
}

System::of(
    Factory::build(),
    InMemory::new(),
)
    ->handle(Url::class, Str::class)
    ->actor(
        Crawler::class,
        static fn($_, Url $url, Spawn $spawn) => new Crawler($spawn, $url),
    )
    ->actor(
        ChildCrawler::class,
        static fn(OperatingSystem $os, Str $tld) => new ChildCrawler($os, $tld->message()),
    )
    ->run(Crawler::class, Url::of('https://www.service-public.fr'));
