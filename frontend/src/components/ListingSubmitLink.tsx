'use client';

import type { ComponentProps } from 'react';
import Link from 'next/link';
import { LISTING_SUBMIT_PATH } from '@/lib/auth-constants';
import { syncAuthCookie } from '@/lib/auth';

type ListingSubmitLinkProps = Omit<ComponentProps<typeof Link>, 'href' | 'prefetch'> & {
    href?: string;
};

/**
 * Ссылка на подачу объявления. Без prefetch (middleware-редирект кэшируется)
 * и с синхронизацией JWT в cookie перед переходом.
 */
export function ListingSubmitLink({
    href = LISTING_SUBMIT_PATH,
    onClick,
    ...props
}: ListingSubmitLinkProps) {
    return (
        <Link
            href={href}
            prefetch={false}
            onClick={(event) => {
                syncAuthCookie();
                onClick?.(event);
            }}
            {...props}
        />
    );
}
