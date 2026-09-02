import type { Metadata } from 'next';
import { Button } from '@/components/ui/button';
import { getSiteOrigin } from '@/lib/site-url';
import { buildOpenGraphMeta } from '@/lib/seo/open-graph';
import { OwnerHero } from '@/features/owner-landing/components/OwnerHero';
import { OwnerReach } from '@/features/owner-landing/components/OwnerReach';
import { OwnerBenefits } from '@/features/owner-landing/components/OwnerBenefits';
import { OwnerSteps } from '@/features/owner-landing/components/OwnerSteps';
import { OwnerPricingSummary } from '@/features/owner-landing/components/OwnerPricingSummary';
import { OwnerTools } from '@/features/owner-landing/components/OwnerTools';
import { OwnerFaq } from '@/features/owner-landing/components/OwnerFaq';
import { OwnerSeoText } from '@/features/owner-landing/components/OwnerSeoText';
import { OwnerLandingCta } from '@/features/owner-landing/components/OwnerLandingCta';
import { OWNER_LANDING_FAQ } from '@/features/owner-landing/faq-data';
import { JsonLdScript } from '@/lib/json-ld/json-ld-script';
import { buildFaqPageJsonLd } from '@/lib/json-ld/faq';

const PAGE_PATH = '/sdat-kvartiru-na-sutki/';
const pageTitle = 'Сдать квартиру на сутки - разместить объявление бесплатно | Posutki.by';
const pageDescription =
    'Разместите объявление о посуточной аренде квартиры, дома или усадьбы на Posutki.by бесплатно. Без комиссии с бронирования, календарь занятости и статистика в личном кабинете.';

export const metadata: Metadata = {
    title: pageTitle,
    description: pageDescription,
    alternates: {
        canonical: `${getSiteOrigin()}${PAGE_PATH}`,
    },
    ...buildOpenGraphMeta({
        title: pageTitle,
        description: pageDescription,
        path: PAGE_PATH,
    }),
};

function OwnerFaqJsonLd() {
    return <JsonLdScript data={buildFaqPageJsonLd(OWNER_LANDING_FAQ)} />;
}

export default function OwnerLandingPage() {
    return (
        <>
            <OwnerFaqJsonLd />
            <OwnerHero />
            <OwnerReach />
            <OwnerBenefits />
            <OwnerSteps />

            <OwnerPricingSummary />
            <OwnerTools />
            <OwnerFaq />
            <OwnerSeoText />

            <section className="py-12 md:py-16 bg-surface">
                <div className="container mx-auto px-4">
                    <div className="mx-auto max-w-xl text-center">
                        <h2 className="mb-3 font-display text-2xl font-bold text-foreground md:text-3xl">
                            Готовы разместить объявление?
                        </h2>
                        <p className="mb-8 text-muted-foreground">
                            Это бесплатно и займёт несколько минут.
                        </p>
                        <Button
                            size="lg"
                            asChild
                            className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0"
                        >
                            <OwnerLandingCta placement="final">
                                Разместить объявление бесплатно
                            </OwnerLandingCta>
                        </Button>
                    </div>
                </div>
            </section>
        </>
    );
}
