<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add city name_prepositional and name_genitive for catalog headings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities ADD name_prepositional VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE cities ADD name_genitive VARCHAR(255) DEFAULT NULL');

        foreach (self::prepositionalBySlug() as $slug => $namePrepositional) {
            $this->addSql(
                'UPDATE cities SET name_prepositional = ? WHERE slug = ?',
                [$namePrepositional, $slug],
            );
        }

        foreach (self::genitiveBySlug() as $slug => $nameGenitive) {
            $this->addSql(
                'UPDATE cities SET name_genitive = ? WHERE slug = ?',
                [$nameGenitive, $slug],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cities DROP name_genitive');
        $this->addSql('ALTER TABLE cities DROP name_prepositional');
    }

    /**
     * Предложный падеж без предлога «в» (как у city_districts.name_prepositional).
     *
     * @return array<string, string>
     */
    private static function prepositionalBySlug(): array
    {
        return [
            'minsk' => 'Минске',
            'brest' => 'Бресте',
            'vitebsk' => 'Витебске',
            'gomel' => 'Гомеле',
            'grodno' => 'Гродно',
            'mogilev' => 'Могилёве',
            'orsha' => 'Орше',
            'svetlogorsk' => 'Светлогорске',
            'smorgon' => 'Сморгони',
            'molodechno' => 'Молодечно',
            'zhodino' => 'Жодино',
            'nesvizh' => 'Несвиже',
            'glubokoe' => 'Глубоком',
            'logoysk' => 'Логойске',
            'baranovichi' => 'Барановичах',
            'pinsk' => 'Пинске',
            'novopolotsk' => 'Новополоцке',
            'bobruysk' => 'Бобруйске',
            'zhlobin' => 'Жлобине',
            'volkovysk' => 'Волковыске',
            'novolukoml' => 'Новолукомле',
            'krichev' => 'Кричеве',
            'lida' => 'Лиде',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function genitiveBySlug(): array
    {
        return [
            'minsk' => 'Минска',
            'brest' => 'Бреста',
            'vitebsk' => 'Витебска',
            'gomel' => 'Гомеля',
            'grodno' => 'Гродно',
            'mogilev' => 'Могилёва',
            'bobruysk' => 'Бобруйска',
        ];
    }
}
