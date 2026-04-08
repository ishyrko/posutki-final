<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Property\Validation;

use App\Domain\Property\Validation\FloorTotalFloorsValidator;
use App\Domain\Shared\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class FloorTotalFloorsValidatorTest extends TestCase
{
    public function testDoesNothingWhenEitherValueMissing(): void
    {
        FloorTotalFloorsValidator::assertValid(null, null);
        FloorTotalFloorsValidator::assertValid(5, null);
        FloorTotalFloorsValidator::assertValid(null, 9);
        $this->addToAssertionCount(1);
    }

    public function testPassesWhenFloorNotAboveTotal(): void
    {
        FloorTotalFloorsValidator::assertValid(3, 9);
        FloorTotalFloorsValidator::assertValid(-1, 9);
        FloorTotalFloorsValidator::assertValid(9, 9);
        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenFloorAboveTotal(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Этаж не может быть больше чем этажей в доме');
        FloorTotalFloorsValidator::assertValid(10, 9);
    }
}
