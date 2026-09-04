import Link from 'next/link';
import { buildCatalogUrl } from '@/features/catalog/slugs';

const CITY_LINKS = [
    { label: 'Минске', region: undefined },
    { label: 'Бресте', region: 'brest' },
    { label: 'Витебске', region: 'vitebsk' },
    { label: 'Гомеле', region: 'gomel' },
    { label: 'Гродно', region: 'grodno' },
    { label: 'Могилёве', region: 'mogilev' },
];

export function OwnerSeoText() {
    return (
        <section className="py-12 md:py-16 bg-background">
            <div className="container mx-auto px-4">
                <div className="mx-auto max-w-3xl">
                    <h2 className="mb-4 font-display text-2xl font-bold text-foreground md:text-3xl">
                        Сдать квартиру посуточно на Posutki.by
                    </h2>
                    <div className="space-y-3 text-sm leading-relaxed text-muted-foreground md:text-base">
                        <p>
                            Если вы хотите сдать квартиру на сутки, разместите объявление на{' '}
                            <span className="font-medium text-foreground">Posutki.by</span> -
                            гости увидят его в каталоге посуточной аренды и свяжутся с вами
                            напрямую, без посредников. Сервис подходит и для сдачи дома, коттеджа
                            или усадьбы.
                        </p>
                        <p>
                            Разместить объявление можно бесплатно в любом городе Беларуси. Бесплатно
                            можно разместить до 5 объявлений (в зависимости от города). Чаще
                            всего объявления ищут в{' '}
                            {CITY_LINKS.map((city, index) => (
                                <span key={city.label}>
                                    <Link
                                        href={buildCatalogUrl({
                                            propertyType: 'apartment',
                                            region: city.region,
                                        })}
                                        className="font-medium text-primary hover:underline"
                                    >
                                        {city.label}
                                    </Link>
                                    {index < CITY_LINKS.length - 1 ? ', ' : ''}
                                </span>
                            ))}
                            , но объявления принимаются из всех регионов страны.
                        </p>
                        <p>
                            Чем подробнее описание и качественнее фотографии, тем быстрее находится
                            гость. После первой публикации объявление автоматически получает
                            бесплатный VIP 1 на 2 недели - это поднимает его выше в каталоге и
                            увеличивает число просмотров.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}
