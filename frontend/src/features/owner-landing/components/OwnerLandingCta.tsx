'use client';

import type { ComponentProps } from 'react';
import { ListingSubmitLink } from '@/components/ListingSubmitLink';
import { trackGaEvent } from '@/lib/gtag';

type OwnerLandingCtaPlacement = 'hero' | 'benefits' | 'steps' | 'pricing' | 'final';

type OwnerLandingCtaProps = Omit<ComponentProps<typeof ListingSubmitLink>, 'onClick'> & {
    placement: OwnerLandingCtaPlacement;
};

/** CTA-ссылка на подачу объявления с трекингом клика по лендингу владельцев. */
export function OwnerLandingCta({ placement, ...props }: OwnerLandingCtaProps) {
    return (
        <ListingSubmitLink
            {...props}
            onClick={() => {
                trackGaEvent('owner_landing_cta_click', { placement });
            }}
        />
    );
}
