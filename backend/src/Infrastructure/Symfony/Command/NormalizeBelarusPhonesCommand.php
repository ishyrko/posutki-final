<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\User\Service\PhoneNumberNormalizer;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:normalize-belarus-phones',
    description: 'Привести сохранённые телефоны к формату +375XXXXXXXXX',
)]
final class NormalizeBelarusPhonesCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Только показать изменения, без записи в БД');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Режим dry-run: изменения в БД не применяются.');
        }

        $updated = 0;
        $skipped = 0;

        $updated += $this->normalizeUsersPhones($io, $dryRun, $skipped);
        $updated += $this->normalizeUserPhonesTable($io, $dryRun, $skipped);
        $updated += $this->normalizeAuthPhoneCodes($io, $dryRun, $skipped);
        $updated += $this->normalizePropertyContactPhones($io, $dryRun, $skipped);

        $io->success(sprintf(
            'Готово: обновлено %d, пропущено %d%s.',
            $updated,
            $skipped,
            $dryRun ? ' (dry-run)' : '',
        ));

        return Command::SUCCESS;
    }

    private function normalizeUsersPhones(SymfonyStyle $io, bool $dryRun, int &$skipped): int
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, phone, is_phone_verified FROM users WHERE phone IS NOT NULL AND phone != \'\'',
        );

        /** @var list<array{id: int|string, old: string, new: string}> $planned */
        $planned = [];
        $verifiedTargets = [];

        foreach ($rows as $row) {
            $normalized = $this->tryNormalize((string) $row['phone']);
            if ($normalized === null) {
                $io->warning(sprintf('users #%s: не удалось нормализовать «%s»', $row['id'], $row['phone']));
                ++$skipped;
                continue;
            }

            if ($normalized === $row['phone']) {
                if ((bool) $row['is_phone_verified']) {
                    $verifiedTargets[$normalized][] = (int) $row['id'];
                }
                continue;
            }

            $planned[] = ['id' => $row['id'], 'old' => (string) $row['phone'], 'new' => $normalized];
            if ((bool) $row['is_phone_verified']) {
                $verifiedTargets[$normalized][] = (int) $row['id'];
            }
        }

        $conflictedIds = [];
        foreach ($verifiedTargets as $phone => $userIds) {
            if (count($userIds) <= 1) {
                continue;
            }
            $conflictedIds += array_fill_keys($userIds, true);
            $io->warning(sprintf(
                'Конфликт verified_phone после нормализации %s: пользователи %s',
                $phone,
                implode(', ', array_map(static fn (int $id): string => (string) $id, $userIds)),
            ));
        }

        $updated = 0;
        foreach ($planned as $change) {
            if (isset($conflictedIds[(int) $change['id']])) {
                ++$skipped;
                continue;
            }

            if ($dryRun) {
                $io->writeln(sprintf('users #%s: %s → %s', $change['id'], $change['old'], $change['new']));
                ++$updated;
                continue;
            }

            try {
                $this->connection->executeStatement(
                    'UPDATE users SET phone = :phone WHERE id = :id',
                    ['phone' => $change['new'], 'id' => $change['id']],
                );
                ++$updated;
                $io->writeln(sprintf('users #%s: %s → %s', $change['id'], $change['old'], $change['new']));
            } catch (\Throwable $e) {
                $io->warning(sprintf('users #%s: пропуск (%s)', $change['id'], $e->getMessage()));
                ++$skipped;
            }
        }

        return $updated;
    }

    private function normalizeUserPhonesTable(SymfonyStyle $io, bool $dryRun, int &$skipped): int
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, user_id, phone FROM user_phones',
        );

        $updated = 0;

        foreach ($rows as $row) {
            $normalized = $this->tryNormalize((string) $row['phone']);
            if ($normalized === null) {
                $io->warning(sprintf('user_phones #%s: не удалось нормализовать «%s»', $row['id'], $row['phone']));
                ++$skipped;
                continue;
            }

            if ($normalized === $row['phone']) {
                continue;
            }

            $duplicate = $this->connection->fetchOne(
                'SELECT id FROM user_phones WHERE user_id = :userId AND phone = :phone AND id != :id LIMIT 1',
                ['userId' => $row['user_id'], 'phone' => $normalized, 'id' => $row['id']],
            );

            if ($duplicate !== false) {
                $io->warning(sprintf(
                    'user_phones #%s: дубликат user_id=%s phone=%s (оставляем #%s)',
                    $row['id'],
                    $row['user_id'],
                    $normalized,
                    $duplicate,
                ));
                if (!$dryRun) {
                    $this->connection->executeStatement(
                        'DELETE FROM user_phones WHERE id = :id',
                        ['id' => $row['id']],
                    );
                }
                ++$skipped;
                continue;
            }

            if ($dryRun) {
                $io->writeln(sprintf('user_phones #%s: %s → %s', $row['id'], $row['phone'], $normalized));
                ++$updated;
                continue;
            }

            try {
                $this->connection->executeStatement(
                    'UPDATE user_phones SET phone = :phone WHERE id = :id',
                    ['phone' => $normalized, 'id' => $row['id']],
                );
                ++$updated;
                $io->writeln(sprintf('user_phones #%s: %s → %s', $row['id'], $row['phone'], $normalized));
            } catch (\Throwable $e) {
                $io->warning(sprintf('user_phones #%s: пропуск (%s)', $row['id'], $e->getMessage()));
                ++$skipped;
            }
        }

        return $updated;
    }

    private function normalizeAuthPhoneCodes(SymfonyStyle $io, bool $dryRun, int &$skipped): int
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['auth_phone_codes'])) {
            return 0;
        }

        $rows = $this->connection->fetchAllAssociative('SELECT id, phone FROM auth_phone_codes');
        $updated = 0;

        foreach ($rows as $row) {
            $normalized = $this->tryNormalize((string) $row['phone']);
            if ($normalized === null || $normalized === $row['phone']) {
                if ($normalized === null) {
                    ++$skipped;
                }
                continue;
            }

            $existing = $this->connection->fetchOne(
                'SELECT id FROM auth_phone_codes WHERE phone = :phone AND id != :id LIMIT 1',
                ['phone' => $normalized, 'id' => $row['id']],
            );

            if ($existing !== false) {
                if ($dryRun) {
                    $io->writeln(sprintf('auth_phone_codes #%s: удалить (дубликат %s)', $row['id'], $normalized));
                } else {
                    $this->connection->executeStatement('DELETE FROM auth_phone_codes WHERE id = :id', ['id' => $row['id']]);
                }
                ++$skipped;
                continue;
            }

            if ($dryRun) {
                $io->writeln(sprintf('auth_phone_codes #%s: %s → %s', $row['id'], $row['phone'], $normalized));
                ++$updated;
                continue;
            }

            $this->connection->executeStatement(
                'UPDATE auth_phone_codes SET phone = :phone WHERE id = :id',
                ['phone' => $normalized, 'id' => $row['id']],
            );
            ++$updated;
        }

        return $updated;
    }

    private function normalizePropertyContactPhones(SymfonyStyle $io, bool $dryRun, int &$skipped): int
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, contact_phone FROM properties WHERE contact_phone IS NOT NULL AND contact_phone != \'\'',
        );

        $updated = 0;

        foreach ($rows as $row) {
            $normalized = $this->tryNormalize((string) $row['contact_phone']);
            if ($normalized === null) {
                $io->warning(sprintf('properties #%s: не удалось нормализовать «%s»', $row['id'], $row['contact_phone']));
                ++$skipped;
                continue;
            }

            if ($normalized === $row['contact_phone']) {
                continue;
            }

            if ($dryRun) {
                $io->writeln(sprintf('properties #%s: %s → %s', $row['id'], $row['contact_phone'], $normalized));
                ++$updated;
                continue;
            }

            $this->connection->executeStatement(
                'UPDATE properties SET contact_phone = :phone WHERE id = :id',
                ['phone' => $normalized, 'id' => $row['id']],
            );
            ++$updated;
        }

        return $updated;
    }

    private function tryNormalize(string $phone): ?string
    {
        try {
            return PhoneNumberNormalizer::normalize($phone);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
