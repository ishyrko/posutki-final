<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Enum\DealType;
use App\Domain\Property\Enum\SellerType;
use App\Domain\Property\Validation\PropertyImageLimitsValidator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

    #[Assert\Type('array')]
    public ?array $paymentMethods = null;

    #[Assert\Range(
        min: 1,
        max: 20,
        notInRangeMessage: 'Максимум гостей: от {{ min }} до {{ max }}'
    )]
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

    #[Assert\Length(max: 255)]
    public ?string $streetName = null;

    #[Assert\Type('array')]
    public ?array $coordinates = null;

    #[Assert\Type('array')]
    public ?array $images = null;

    #[Assert\Type('array')]
    public ?array $amenities = null;

    #[Assert\Choice(callback: [SellerType::class, 'values'], message: 'Недопустимый тип продавца')]
    public ?string $sellerType = null;

    #[Assert\Type('bool')]
    public ?bool $weekendPriceNegotiable = null;

    #[Assert\Type('array')]
    public ?array $additionalServices = null;

    #[Assert\Length(max: 500)]
    public ?string $instagramUrl = null;

    #[Assert\Length(max: 500)]
    public ?string $websiteUrl = null;

    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(message: 'URL календаря не может быть пустым'),
        new Assert\Url(message: 'Некорректный URL календаря'),
        new Assert\Length(max: 2000),
    ])]
    public ?array $externalCalendarUrls = null;

    #[Assert\Callback]
    public function validateImages(ExecutionContextInterface $context): void
    {
        if ($this->images === null || $this->type === null) {
            return;
        }

        $count = count($this->images);
        if ($count < PropertyImageLimitsValidator::MIN) {
            $context->buildViolation(sprintf('Загрузите не менее %d фотографий', PropertyImageLimitsValidator::MIN))
                ->atPath('images')
                ->addViolation();

            return;
        }

        $max = PropertyImageLimitsValidator::maxForType($this->type);
        if ($count > $max) {
            $context->buildViolation(sprintf('Не более %d фотографий', $max))
                ->atPath('images')
                ->addViolation();
        }
    }

    #[Assert\Callback]
    public function validateApartmentAddress(ExecutionContextInterface $context): void
    {
        if ($this->type !== PropertyType::Apartment->value) {
            return;
        }

        if ($this->streetId === null && trim($this->streetName ?? '') === '') {
            $context->buildViolation('Укажите улицу')
                ->atPath('streetName')
                ->addViolation();
        }

        if (trim($this->building ?? '') === '') {
            $context->buildViolation('Укажите номер дома')
                ->atPath('building')
                ->addViolation();
        }
    }
}
