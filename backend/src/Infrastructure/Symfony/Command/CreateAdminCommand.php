<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\User\Entity\User;
use App\Domain\User\ValueObject\Email;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email')
            ->addArgument('password', InputArgument::REQUIRED, 'Admin password')
            ->addArgument('firstName', InputArgument::OPTIONAL, 'First name', 'Admin')
            ->addArgument('lastName', InputArgument::OPTIONAL, 'Last name', 'Admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');

        $conn = $this->em->getConnection();
        $row = $conn->fetchAssociative('SELECT id FROM users WHERE email = ?', [$email]);

        if ($row) {
            $existing = $this->em->getRepository(User::class)->find($row['id']);
            $existing->grantRole('ROLE_ADMIN');
            $this->em->flush();
            $io->success(sprintf('Existing user "%s" has been granted ROLE_ADMIN.', $email));

            return Command::SUCCESS;
        }

        $user = User::register(
            Email::fromString($email),
            '',
            $firstName,
            $lastName,
        );

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->changePassword($hashedPassword);
        $user->verify();
        $user->grantRole('ROLE_ADMIN');

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Admin user "%s" created successfully.', $email));

        return Command::SUCCESS;
    }
}
