<?php

declare(strict_types=1);

namespace App\Domain\ContactFeedback\Repository;

use App\Domain\ContactFeedback\Entity\ContactFeedback;

interface ContactFeedbackRepositoryInterface
{
    public function save(ContactFeedback $feedback): void;

    public function findById(string $id): ?ContactFeedback;
}
