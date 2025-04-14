<?php
declare(strict_types = 1);

namespace Innmind\Witness\Message\Payload;

use Innmind\Witness\{
    Actor\Address,
    Actor\Address\Name,
    Message\Payload,
    Adapter\Mailboxes,
};
use Innmind\OperatingSystem\OperatingSystem;
use Innmind\TimeContinuum\PointInTime;
use Innmind\Validation\{
    Is,
    Of,
    Instance,
    Failure,
};
use Innmind\Immutable\{
    Maybe,
    Validation,
};
use Ramsey\Uuid\Uuid;

/**
 * @internal
 */
final class Value
{
    private const ADDRESS = 'innmind-witness-address';
    private const POINT_IN_TIME = 'innmind-witness-point-in-time';

    /**
     * @psalm-pure
     */
    public static function normalize(string|int|float|bool|Payload|Address|PointInTime|null $value): string|int|float|bool|array|null
    {
        return match (true) {
            $value instanceof Payload => $value->unwrap()->normalize(),
            $value instanceof Address => [
                'id' => self::ADDRESS,
                'address' => $value->name()->toString(),
            ],
            $value instanceof PointInTime => [
                'id' => self::POINT_IN_TIME,
                'pointInTime' => $value->format(new PointInTimeFormat),
            ],
            default => $value,
        };
    }

    /**
     * @psalm-pure
     *
     * @return Maybe<string|int|float|bool|Payload|Address|PointInTime|null>
     */
    public static function denormalize(
        OperatingSystem $os,
        Mailboxes $mailboxes,
        Name $currentActor,
        mixed $value,
    ): Maybe {
        $validate = Is::string()
            ->or(Is::int())
            ->or(Is::float())
            ->or(Is::bool())
            ->or(Is::null())
            ->or(
                Is::shape(
                    'id',
                    Is::string()->and(Of::callable(static fn(string $id) => match ($id) {
                        self::ADDRESS => Validation::success($id),
                        default => Validation::fail(Failure::of('Not an address')),
                    })),
                )
                    ->with(
                        'address',
                        Is::string()
                            ->map(static fn($string) => Maybe::of(match (Uuid::isValid($string)) {
                                true => Uuid::fromString($string),
                                false => null,
                            }))
                            ->and(Is::just())
                            ->map(Name::of(...))
                            ->or(
                                Is::string()
                                    ->map(static fn($string) => Maybe::of(match ($string) {
                                        'root' => Name::root(),
                                        default => null,
                                    }))
                                    ->and(Is::just()),
                            )
                            ->map(static fn($name) => $mailboxes->address(
                                $os,
                                $name,
                                $currentActor,
                            )),
                    )
                    ->map(static fn($shape): mixed => $shape['address'])
                    ->and(Instance::of(Address::class)),
            )
            ->or(
                Is::shape(
                    'id',
                    Is::string()->and(Of::callable(
                        static fn(string $id) => match ($id) {
                            self::POINT_IN_TIME => Validation::success($id),
                            default => Validation::fail(Failure::of('Not a point in time')),
                        },
                    )),
                )
                    ->with(
                        'pointInTime',
                        Is::string()
                            ->map(static fn($string) => $os->clock()->at(
                                $string,
                                new PointInTimeFormat,
                            ))
                            ->and(Is::just()),
                    )
                    ->map(static fn($shape): mixed => $shape['pointInTime'])
                    ->and(Instance::of(PointInTime::class)),
            )
            ->or(
                Is::array()
                    ->map(static fn($value) => Payload::denormalize(
                        $os,
                        $mailboxes,
                        $currentActor,
                        $value,
                    ))
                    ->and(Is::just()),
            );

        return $validate($value)->maybe();
    }
}
