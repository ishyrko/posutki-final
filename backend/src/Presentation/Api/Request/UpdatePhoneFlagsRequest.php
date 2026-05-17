<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

class UpdatePhoneFlagsRequest
{
    public bool $hasViber = false;

    public bool $hasWhatsapp = false;
}
