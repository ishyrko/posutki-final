<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Payment\BePaid;

use App\Infrastructure\Payment\BePaid\BePaidSignatureVerifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class BePaidSignatureVerifierTest extends TestCase
{
    public function testSkipsVerificationWhenPublicKeyNotConfigured(): void
    {
        $verifier = new BePaidSignatureVerifier(new NullLogger(), '');

        self::assertTrue($verifier->verify('{"token":"x"}', null));
    }

    public function testRejectsMissingSignatureWhenPublicKeyConfigured(): void
    {
        $verifier = new BePaidSignatureVerifier(new NullLogger(), 'invalid-key-material');

        self::assertFalse($verifier->verify('{"token":"x"}', null));
    }
}
