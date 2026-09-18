<?php

declare(strict_types=1);

namespace App\Infrastructure\Migration\Data;

/**
 * Split of the Arendom partner into two accounts by city (manager table).
 *
 * Cities not in either list stay with the original (first) account.
 */
final class ArendomPartnerSplitData
{
    public const EXTERNAL_SOURCE = 'arendom';

    public const FIRST_PHONE = '+375293197117';

    public const SECOND_PHONE = '+375447076448';

    public const SECOND_PARTNER_FIRST_NAME = 'Ольга-Диана';

    /**
     * Юля-Саша. Remaining listings stay on the original user.
     *
     * @var list<string>
     */
    public const FIRST_PARTNER_CITY_SLUGS = [
        'baranovichi',
        'beloozersk',
        'bobruysk',
        'borisov',
        'bragin-gp',
        'brest',
        'vileyka',
        'vitebsk',
        'gantsevichi',
        'glubokoe',
        'gomel',
        'grodno',
        'dzerzhinsk',
        'dokshitsy',
        'drogichin',
        'zhabinka',
        'zelva-gp',
        'ivanovo',
        'kalinkovichi',
        'kletsk',
        'kobrin',
        'kostyukovichi',
        'krichev',
        'lida',
        'luninets',
        'marina-gorka',
        'mikashevichi',
        'minsk',
        'miory',
        'mozyr',
        'mogilev',
        'molodechno',
        'mosty',
        'mstislavl',
        'myadel',
        'nesvizh',
        'novolukoml',
        'novopolotsk',
        'orsha',
        'ostrovets',
        'oshmyany',
        'petrikov',
        'pinsk',
        'polotsk',
        'postavy',
        'pruzhany',
        'senno',
        'smorgon',
        'starye-dorogi',
        'stolbtsy',
        'stolin',
        'uzda-g',
        'fanipol',
        'chechersk',
        'hoyniki',
    ];

    /**
     * Менеджер Ольга-Диана. These listings move to the second account.
     *
     * @var list<string>
     */
    public const SECOND_PARTNER_CITY_SLUGS = [
        'bereza',
        'buda-koshelevo',
        'verhnedvinsk',
        'volkovysk',
        'volozhin',
        'gorki',
        'dobrush',
        'druzhnyy-p',
        'dyatlovo',
        'zhitkovichi',
        'zhlobin',
        'zhodino',
        'ivatsevichi',
        'ive',
        'kamenets',
        'klimovichi',
        'korma-gp',
        'lelchitsy-gp',
        'logoysk',
        'machulischi-gp',
        'novogrudok',
        'osipovichi',
        'rechitsa',
        'rogachev',
        'svetlogorsk',
        'svisloch',
        'smolevichi',
        'hotimsk-gp',
        'lyuban',
        'slutsk',
        'soligorsk',
        'lepel',
        'elsk',
    ];

    public static function isSecondPartnerCity(string $slug): bool
    {
        return in_array($slug, self::SECOND_PARTNER_CITY_SLUGS, true);
    }
}
