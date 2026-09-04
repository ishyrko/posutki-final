import Link from 'next/link';
import { Check } from 'lucide-react';
import { MAX_VISIBLE_PHOTOS_FREE_PLACEMENT } from '@/features/placement/types';

const PRICING_CARDS = [
    {
        title: 'Бесплатное размещение',
        desc: `Объявление в каталоге без ограничения по времени. Бесплатно можно разместить до 5 объявлений (в зависимости от города). На карточке показывается до ${MAX_VISIBLE_PHOTOS_FREE_PLACEMENT} фотографий.`,
    },
    {
        title: 'Бесплатный VIP 1 на 2 недели',
        desc: 'Один раз на аккаунт: после первой публикации объявление автоматически поднимается выше в каталоге на 2 недели.',
    },
    {
        title: 'VIP-уровни по подписке',
        desc: 'Более высокая позиция в каталоге. Скидка 5% при оплате на 3 месяца, 10% - на 6 месяцев, 20% - на 12 месяцев.',
    },
];

export function OwnerPricingSummary() {
    return (
        <section className="py-12 md:py-16 bg-background">
            <div className="container mx-auto px-4">
                <div className="mx-auto max-w-2xl text-center mb-10 md:mb-12">
                    <h2 className="font-display text-2xl font-bold text-foreground md:text-3xl">
                        Сколько это стоит
                    </h2>
                    <p className="mt-3 text-muted-foreground">
                        Размещение объявления бесплатное (с лимитом по количеству). Платное
                        продвижение - по желанию, если нужна более высокая позиция в каталоге.
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                    {PRICING_CARDS.map((card) => (
                        <div
                            key={card.title}
                            className="rounded-xl border border-border bg-card p-5 shadow-card"
                        >
                            <div className="mb-3 flex items-center gap-2">
                                <Check className="h-4 w-4 shrink-0 text-primary" />
                                <h3 className="font-semibold text-foreground">{card.title}</h3>
                            </div>
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                {card.desc}
                            </p>
                        </div>
                    ))}
                </div>

                <p className="mt-6 text-center text-sm text-muted-foreground">
                    Точные цены зависят от города или области -{' '}
                    <Link href="/tarify/" className="font-medium text-primary hover:underline">
                        смотрите цены по городам
                    </Link>
                    .
                </p>
            </div>
        </section>
    );
}
