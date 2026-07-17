<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Mail\PlacementMailer;
use App\Infrastructure\Service\FrontendUrlBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notify-vip-expiring-soon',
    description: 'Email owners whose VIP placement expires within 24 hours',
)]
class NotifyVipExpiringSoonCommand extends Command
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PlacementMailer $mailer,
        private readonly FrontendUrlBuilder $frontendUrls,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $until = $now->modify('+24 hours');

        $properties = $this->propertyRepository->findWithPlacementLevelExpiringSoon($now, $until);
        $sent = 0;
        $skipped = 0;

        foreach ($properties as $property) {
            $owner = $this->userRepository->findById($property->getOwnerId());
            if ($owner === null || $owner->getEmail()?->getValue() === null) {
                ++$skipped;

                continue;
            }

            $this->mailer->sendVipExpiringSoon(
                property: $property,
                owner: $owner,
                propertyUrl: $this->frontendUrls->publicPropertyForListing($property),
                listingsUrl: $this->frontendUrls->myListings(),
                dashboardUrl: $this->frontendUrls->cabinet(),
            );

            $property->markPlacementLevelExpiryReminded($now);
            $this->propertyRepository->save($property);
            ++$sent;
        }

        $io->success(sprintf(
            'Sent %d VIP expiry reminder(s), skipped %d (no owner/email).',
            $sent,
            $skipped,
        ));

        return Command::SUCCESS;
    }
}
