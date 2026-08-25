'use client';

import Link from 'next/link';
import { useMemo, useState } from 'react';
import { Star, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useOwnerPropertyReviews, useOwnerReviews, useReplyToReview } from '../hooks';
import type { OwnerReviewItem } from '../types';
import { toast } from 'sonner';

type OwnerReviewsPageProps = {
    propertyId?: number;
};

function authorLabel(author: OwnerReviewItem['author']): string {
    const full = `${author.firstName ?? ''} ${author.lastName ?? ''}`.trim();
    return full || 'Пользователь';
}

function ReviewReplyCard({ review, propertyId }: { review: OwnerReviewItem; propertyId?: number }) {
    const [text, setText] = useState(review.ownerReply ?? '');
    const reply = useReplyToReview(propertyId);

    const handleSubmit = () => {
        const trimmed = text.trim();
        if (!trimmed) {
            toast.error('Напишите текст ответа');
            return;
        }
        reply.mutate(
            { reviewId: review.id, text: trimmed },
            {
                onSuccess: () => toast.success('Ответ сохранён'),
                onError: () => toast.error('Не удалось сохранить ответ'),
            },
        );
    };

    return (
        <article className="rounded-xl border border-border/60 bg-card/50 p-4 shadow-sm space-y-3">
            <div className="flex flex-wrap items-center gap-2">
                <div className="flex items-center gap-0.5 text-amber-500">
                    {Array.from({ length: 5 }).map((_, i) => (
                        <Star
                            key={i}
                            className={`w-3.5 h-3.5 ${i < review.rating ? 'fill-current' : 'text-muted-foreground/30'}`}
                        />
                    ))}
                </div>
                <span className="text-xs text-muted-foreground">
                    {new Date(review.createdAt).toLocaleDateString('ru-RU', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                    })}
                </span>
            </div>

            {propertyId == null ? (
                <p className="text-sm">
                    <Link href={`/kabinet/otzyvy/${review.property.id}/`} className="text-primary hover:underline font-medium">
                        {review.property.title}
                    </Link>
                </p>
            ) : null}

            <p className="text-sm text-muted-foreground whitespace-pre-line">{review.text}</p>

            <div className="text-sm text-muted-foreground space-y-1">
                <p className="font-medium text-foreground">{authorLabel(review.author)}</p>
                {review.shareDataWithOwner ? (
                    <>
                        {review.author.phone ? <p>Телефон: {review.author.phone}</p> : null}
                        {review.author.email ? <p>Email: {review.author.email}</p> : null}
                    </>
                ) : (
                    <p className="text-xs">Контакты не переданы</p>
                )}
            </div>

            <div className="space-y-2 pt-2 border-t border-border/60">
                <Label htmlFor={`reply-${review.id}`}>Ваш ответ</Label>
                <Textarea
                    id={`reply-${review.id}`}
                    value={text}
                    onChange={(e) => setText(e.target.value)}
                    rows={3}
                    placeholder="Ответ гостю…"
                />
                <Button type="button" size="sm" disabled={reply.isPending} onClick={handleSubmit}>
                    {review.ownerReply ? 'Изменить ответ' : 'Ответить'}
                </Button>
            </div>
        </article>
    );
}

export function OwnerReviewsPage({ propertyId }: OwnerReviewsPageProps) {
    const allQuery = useOwnerReviews();
    const propertyQuery = useOwnerPropertyReviews(propertyId ?? 0);
    const query = propertyId ? propertyQuery : allQuery;

    const items = useMemo(() => query.data?.items ?? [], [query.data?.items]);
    const propertyTitle = propertyId ? propertyQuery.data?.property?.title : null;

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-foreground">
                    {propertyId ? 'Отзывы по объявлению' : 'Отзывы'}
                </h1>
                {propertyTitle ? (
                    <p className="text-sm text-muted-foreground mt-1">{propertyTitle}</p>
                ) : (
                    <p className="text-sm text-muted-foreground mt-1">Ответы на отзывы по вашим объявлениям</p>
                )}
                {propertyId ? (
                    <Button variant="link" className="px-0 h-auto mt-2" asChild>
                        <Link href="/kabinet/otzyvy/">Все отзывы</Link>
                    </Button>
                ) : null}
            </div>

            {query.isLoading ? (
                <div className="flex items-center gap-2 text-muted-foreground">
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Загрузка…
                </div>
            ) : items.length === 0 ? (
                <p className="text-sm text-muted-foreground">Пока нет опубликованных отзывов.</p>
            ) : (
                <div className="space-y-4">
                    {items.map((review) => (
                        <ReviewReplyCard key={review.id} review={review} propertyId={propertyId} />
                    ))}
                </div>
            )}
        </div>
    );
}
