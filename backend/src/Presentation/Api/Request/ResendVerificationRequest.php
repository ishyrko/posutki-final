<?php

declare(strict_types=1);

namespace App\Presentation\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ResendVerificationRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;
}
