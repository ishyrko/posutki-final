<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\ArticleHtmlNormalizer;
use App\Application\Service\ArticleTextSanitizer;
use App\Application\Service\CatalogPlaceContentNormalizer;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replace the odd nationwide estate FAQ about renting castles.
 */
final class Version20260927143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace the nationwide estate FAQ question about renting castles';
    }

    public function up(Schema $schema): void
    {
        $normalizer = new CatalogPlaceContentNormalizer(
            new ArticleHtmlNormalizer(),
            new ArticleTextSanitizer(),
        );
        $replacement = $normalizer->normalizeFaq([[
            'question' => 'На какие даты усадьбу стоит брать заранее?',
            'answer' => 'На тёплые выходные и праздники дома у Нарочи, на Браславе и в Беловежской пуще уходят раньше. В будни тот же адрес часто свободен ближе к заезду. Открыты ли ваши числа, подтверждает владелец.',
        ]])[0];

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, catalog_faq FROM nationwide_house_catalog_contents',
        );

        foreach ($rows as $row) {
            $faq = json_decode((string) $row['catalog_faq'], true);
            if (!is_array($faq)) {
                continue;
            }

            $changed = false;
            foreach ($faq as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }
                $question = isset($item['question']) && is_string($item['question']) ? $item['question'] : '';
                if (!str_contains($question, 'замок')) {
                    continue;
                }
                $faq[$index] = $replacement;
                $changed = true;
            }

            if (!$changed) {
                continue;
            }

            $this->addSql(
                'UPDATE nationwide_house_catalog_contents SET catalog_faq = ? WHERE id = ?',
                [
                    json_encode(
                        array_values($faq),
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                    ),
                    (int) $row['id'],
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'The previous castle FAQ question was replaced and is not restored.',
        );
    }
}
