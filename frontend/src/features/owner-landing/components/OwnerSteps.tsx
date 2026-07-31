import { Button } from '@/components/ui/button';
import { OwnerLandingCta } from './OwnerLandingCta';

const STEPS = [
    {
        number: '1',
        title: 'Зарегистрируйтесь',
        desc: 'Создайте аккаунт по email, телефону или через Google — это займёт пару минут. Подтвердите контактные данные.',
    },
    {
        number: '2',
        title: 'Опишите жильё',
        desc: 'Добавьте адрес, фото, описание и цену за сутки - форма подскажет, что важно указать.',
    },
    {
        number: '3',
        title: 'Дождитесь модерации',
        desc: 'Модератор проверит объявление, обычно в течение рабочего дня.',
    },
    {
        number: '4',
        title: 'Получайте обращения',
        desc: 'Объявление публикуется в каталоге, а звонки и сообщения от гостей приходят вам напрямую.',
    },
];

export function OwnerSteps() {
    return (
        <section className="py-12 md:py-16 bg-surface">
            <div className="container mx-auto px-4">
                <div className="mx-auto max-w-2xl text-center mb-10 md:mb-12">
                    <h2 className="font-display text-2xl font-bold text-foreground md:text-3xl">
                        Как разместить объявление
                    </h2>
                </div>

                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {STEPS.map((step) => (
                        <div
                            key={step.number}
                            className="rounded-2xl bg-card p-6 shadow-card"
                        >
                            <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground font-display font-bold">
                                {step.number}
                            </div>
                            <h3 className="mb-2 font-display font-semibold text-foreground">
                                {step.title}
                            </h3>
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                {step.desc}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="mt-10 flex justify-center">
                    <Button
                        size="lg"
                        asChild
                        className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0"
                    >
                        <OwnerLandingCta placement="steps">
                            Разместить объявление
                        </OwnerLandingCta>
                    </Button>
                </div>
            </div>
        </section>
    );
}
