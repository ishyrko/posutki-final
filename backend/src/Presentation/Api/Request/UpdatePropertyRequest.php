<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\SellerType;
use Symfony\Component\Validator\Constraints as Assert;

class UpdatePropertyRequest
{
    #[Assert\Choice(
        callback: [PropertyType::class, 'values'],
        message: 'Недопустимый тип объекта'
    )]
    public ?string $type = null;

    #[Assert\Choice(
        callback: [DealType::class, 'values'],
        message: 'Недопустимый тип сделки'
    )]
    public ?string $dealType = null;

    #[Assert\Length(
        min: 10,
        max: 200,
        minMessage: 'Заголовок не короче {{ limit }} символов',
        maxMessage: 'Заголовок не длиннее {{ limit }} символов'
    )]
    public ?string $title = null;

    #[Assert\Length(
        min: 50,
        max: 5000,
        minMessage: 'Описание не короче {{ limit }} символов',
        maxMessage: 'Описание не длиннее {{ limit }} символов'
    )]
    public ?string $description = null;

    #[Assert\Type('array')]
    public ?array $price = null;

    #[Assert\PositiveOrZero(message: 'Площадь не может быть отрицательной')]
    #[Assert\Range(
        min: 0,
        max: 10000,
        notInRangeMessage: 'Площадь от {{ min }} до {{ max }} м²'
    )]
    public ?float $area = null;

    #[Assert\Positive(message: 'Площадь участка должна быть положительной')]
    public ?float $landArea = null;

    #[Assert\Positive(message: 'Количество комнат должно быть положительным')]
    #[Assert\Range(min: 1, max: 50)]
    public ?int $rooms = null;

    #[Assert\Range(min: -5, max: 200)]
    public ?int $floor = null;

    #[Assert\Positive(message: 'Этажность дома должна быть положительной')]
    #[Assert\Range(min: 1, max: 200)]
    public ?int $totalFloors = null;

    #[Assert\Range(min: 0, max: 10)]
    public ?int $bathrooms = null;

    #[Assert\Range(min: 1000, max: 2050)]
    public ?int $yearBuilt = null;

    public ?string $renovation = null;

    public ?string $balcony = null;

    #[Assert\Positive(message: 'Жилая площадь должна быть положительной')]
    public ?float $livingArea = null;

    #[Assert\Positive(message: 'Площадь кухни должна быть положительной')]
    public ?float $kitchenArea = null;

    #[Assert\Positive(message: 'Количество комнат в сделке должно быть положительным')]
    #[Assert\Range(min: 1, max: 50)]
    public ?int $roomsInDeal = null;

    #[Assert\Positive(message: 'Площадь комнат в сделке должна быть положительной')]
    #[Assert\Range(
        min: 0.1,
        max: 10000,
        notInRangeMessage: 'Площадь комнат от {{ min }} до {{ max }} м²'
    )]
    public ?float $roomsArea = null;

    #[Assert\Type('array')]
    public ?array $dealConditions = null;

    #[Assert\Positive(message: 'Максимальное число гостей должно быть положительным')]
    public ?int $maxDailyGuests = null;

    #[Assert\Range(min: 0, max: 50, notInRangeMessage: 'Число односпальных кроватей от {{ min }} до {{ max }}')]
    public ?int $dailySingleBeds = null;

    #[Assert\Range(min: 0, max: 50, notInRangeMessage: 'Число двуспальных кроватей от {{ min }} до {{ max }}')]
    public ?int $dailyDoubleBeds = null;

    #[Assert\Regex(
        pattern: '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
        message: 'Время заезда укажите в формате ЧЧ:ММ'
    )]
    public ?string $checkInTime = null;

    #[Assert\Regex(
        pattern: '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
        message: 'Время выезда укажите в формате ЧЧ:ММ'
    )]
    public ?string $checkOutTime = null;

    public ?string $building = null;

    public ?string $block = null;

    #[Assert\Positive(message: 'ID города должен быть положительным')]
    public ?int $cityId = null;

    #[Assert\Positive(message: 'ID улицы должен быть положительным')]
    public ?int $streetId = null;

    #[Assert\Type('array')]
    public ?array $coordinates = null;

    #[Assert\Type('array')]
    public ?array $images = null;

    #[Assert\Type('array')]
    public ?array $amenities = null;

    #[Assert\Choice(callback: [SellerType::class, 'values'], message: 'Недопустимый тип продавца')]
    public ?string $sellerType = null;
}
