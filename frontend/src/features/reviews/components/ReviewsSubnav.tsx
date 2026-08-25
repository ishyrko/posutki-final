'use client';

import Link from 'next/link';
import { cn } from '@/lib/utils';

export type ReviewsSection = 'incoming' | 'mine';

type ReviewsSubnavProps = {
    active: ReviewsSection;
    hasMyProperties: boolean;
};

export function ReviewsSubnav({ active, hasMyProperties }: ReviewsSubnavProps) {

    return (
        <div className="flex flex-wrap gap-2">
            {hasMyProperties ? (
                <Link
                    href="/kabinet/otzyvy/"
                    className={cn(
                        'inline-flex items-center rounded-full border px-3 py-1.5 text-sm transition-colors',
                        active === 'incoming'
                            ? 'border-primary/30 bg-primary/10 text-primary'
                            : 'border-border text-muted-foreground hover:bg-muted hover:text-foreground',
                    )}
                >
                    На мои объявления
                </Link>
            ) : null}
            <Link
                href="/kabinet/otzyvy/moi/"
                className={cn(
                    'inline-flex items-center rounded-full border px-3 py-1.5 text-sm transition-colors',
                    active === 'mine'
                        ? 'border-primary/30 bg-primary/10 text-primary'
                        : 'border-border text-muted-foreground hover:bg-muted hover:text-foreground',
                )}
            >
                Мои отзывы
            </Link>
        </div>
    );
}
