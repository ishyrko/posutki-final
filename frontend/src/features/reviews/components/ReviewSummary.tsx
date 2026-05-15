'use client';

import { Star } from 'lucide-react';

type ReviewSummaryProps = {
    ratingAvg: number | null;
    reviewCount: number;
};

export function ReviewSummary({ ratingAvg, reviewCount }: ReviewSummaryProps) {
    if (reviewCount <= 0 || ratingAvg == null) {
        return null;
    }

    return (
        <div className="flex items-center gap-2 mb-4">
            <div className="flex items-center gap-0.5 text-amber-500">
                <Star className="w-5 h-5 fill-current" />
            </div>
            <span className="text-lg font-semibold text-foreground">{ratingAvg.toFixed(1)}</span>
            <span className="text-sm text-muted-foreground">({reviewCount} {reviewCount === 1 ? 'отзыв' : reviewCount < 5 ? 'отзыва' : 'отзывов'})</span>
        </div>
    );
}
