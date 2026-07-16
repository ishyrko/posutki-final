<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Domain\Payment\Entity\Payment;
use App\Domain\Payment\Enum\PaymentStatus;
use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Entity\PropertyPlacementPurchase;
use App\Domain\Property\Enum\PlacementPurchaseKind;
use App\Domain\Property\Enum\PlacementPurchaseStatus;
use App\Domain\Property\Enum\PropertyType;

final class PaymentWebhookControllerTest extends ApiTestCase
{
    public function testWebhookWithEmptyPublicKeyAcceptsPayloadAndActivatesPurchase(): void
    {
        $email = 'payment-owner@example.com';
        $password = 'Password123!';
        $owner = $this->createUser($email, $password);
        $city = $this->createCity('Minsk Pay', 'minsk-pay', 'г. Минск');
        $property = $this->createProperty($owner, $city, 'published');

        $levelPrice = new PropertyPlacementLevelPrice(
            propertyType: PropertyType::Apartment->value,
            cityId: $city->getId(),
            regionId: null,
            level: 1,
            priceBynPerMonth: 49,
        );
        $this->entityManager()->persist($levelPrice);
        $this->entityManager()->flush();

        $purchase = new PropertyPlacementPurchase(
            propertyId: $property->getId()->getValue(),
            ownerId: $owner->getId(),
            kind: PlacementPurchaseKind::Level->value,
            priceByn: 49,
            source: 'self_service',
            level: 1,
            levelPriceId: $levelPrice->getId(),
            durationMonths: 1,
        );
        $this->entityManager()->persist($purchase);
        $this->entityManager()->flush();

        $purchaseId = (int) $purchase->getId();
        $payment = new Payment($purchaseId, 'plc-' . $purchaseId . '-functional', 49);
        $payment->setCheckoutToken('functional-checkout-token');
        $this->entityManager()->persist($payment);
        $this->entityManager()->flush();

        $payload = [
            'token' => 'functional-checkout-token',
            'status' => 'successful',
            'order' => [
                'tracking_id' => 'plc-' . $purchaseId . '-functional',
                'amount' => 4900,
                'currency' => 'BYN',
            ],
            'uid' => 'functional-txn-uid',
        ];
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->client->request(
            'POST',
            '/api/webhooks/bepaid',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $rawBody,
        );

        self::assertSame(200, $this->client->getResponse()->getStatusCode());

        $response = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($response['success']);

        $this->entityManager()->clear();
        $reloadedPurchase = $this->entityManager()
            ->getRepository(PropertyPlacementPurchase::class)
            ->find($purchaseId);
        self::assertNotNull($reloadedPurchase);
        self::assertSame(PlacementPurchaseStatus::Active->value, $reloadedPurchase->getStatus());

        $reloadedPayment = $this->entityManager()
            ->getRepository(Payment::class)
            ->find($payment->getId());
        self::assertNotNull($reloadedPayment);
        self::assertSame(PaymentStatus::Successful->value, $reloadedPayment->getStatus());
    }

    public function testWebhookRejectsInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/api/webhooks/bepaid',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: 'not-json',
        );

        self::assertSame(400, $this->client->getResponse()->getStatusCode());
    }
}
