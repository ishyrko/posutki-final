'use client';

import { Star } from 'lucide-react';
import type { PropertyReview } from '../types';

type ReviewListProps = {
    items: PropertyReview[];
};

function authorLabel(author: PropertyReview['author']): string {
    const first = author.firstName?.trim() ?? '';
    const last = author.lastName?.trim() ?? '';
    const full = `${first} ${last}`.trim();
    return full || 'Пользователь';
}

function initials(author: PropertyReview['author']): string {
    const label = authorLabel(author);
    const parts = label.split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase();
    }
    return label.slice(0, 2).toUpperCase() || '?';
}

export function ReviewList({ items }: ReviewListProps) {
    if (items.length === 0) {
        return <p className="text-sm text-muted-foreground">Пока нет опубликованных отзывов.</p>;
    }

    return (
        <ul className="space-y-4">
            {items.map((r) => (
                <li
                    key={r.id}
                    className="rounded-xl border border-border/60 bg-card/50 p-4 shadow-sm"
                >
                    <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold text-foreground">
                            {initials(r.author)}
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-2 mb-1">
                                <span className="font-medium text-foreground">{authorLabel(r.author)}</span>
                                <div className="flex items-center gap-0.5 text-amber-500">
                                    {Array.from({ length: 5 }).map((_, i) => (
                                        <Star
                                            key={i}
                                            className={`w-3.5 h-3.5 ${i < r.rating ? 'fill-current' : 'text-muted-foreground/30'}`}
                                        />
                                    ))}
                                </div>
                                <span className="text-xs text-muted-foreground">
                                    {new Date(r.createdAt).toLocaleDateString('ru-RU', {
                                        day: 'numeric',
                                        month: 'long',
                                        year: 'numeric',
                                    })}
                                </span>
                            </div>
                            {r.text ? (
                                <p className="text-sm text-muted-foreground whitespace-pre-line">{r.text}</p>
                            ) : (
                                <p className="text-sm text-muted-foreground italic">Без комментария</p>
                            )}
                        </div>
                    </div>
                </li>
            ))}
        </ul>
    );
}
