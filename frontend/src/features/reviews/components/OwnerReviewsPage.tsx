'use client';

import Link from 'next/link';
import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Star, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useHasMyProperties } from '@/features/properties/hooks';
import { useOwnerPropertyReviews, useOwnerReviews, useReplyToReview } from '../hooks';
import type { OwnerReviewItem } from '../types';
import { ReviewsSubnav } from './ReviewsSubnav';
import { toast } from 'sonner';

type OwnerReviewsPageProps = {
    propertyId?: number;
};

function authorLabel(author: OwnerReviewItem['author']): string {
    const full = `${author.firstName ?? ''} ${author.lastName ?? ''}`.trim();
    return full || 'Пользователь';
}

type ReviewReplyCardProps = {
    review: OwnerReviewItem;
    propertyId?: number;
    onReply: (reviewId: number, text: string) => void;
    isReplyPending: boolean;
};

function ReviewReplyCard({ review, propertyId, onReply, isReplyPending }: ReviewReplyCardProps) {
    const [text, setText] = useState(review.ownerReply ?? '');

    const handleSubmit = () => {
        const trimmed = text.trim();
        if (!trimmed) {
            toast.error('Напишите текст ответа');
            return;
        }
        onReply(review.id, trimmed);
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
                <Button type="button" size="sm" disabled={isReplyPending} onClick={handleSubmit}>
                    {review.ownerReply ? 'Изменить ответ' : 'Ответить'}
                </Button>
            </div>
        </article>
    );
}

export function OwnerReviewsPage({ propertyId }: OwnerReviewsPageProps) {
    const router = useRouter();
    const { hasMyProperties, isLoading: isOwnerLoading } = useHasMyProperties();
    const ownerQuery = useOwnerReviews(propertyId == null);
    const propertyQuery = useOwnerPropertyReviews(propertyId);
    const query = propertyId != null ? propertyQuery : ownerQuery;
    const reply = useReplyToReview(propertyId);

    useEffect(() => {
        if (propertyId != null || isOwnerLoading || hasMyProperties) {
            return;
        }
        router.replace('/kabinet/otzyvy/moi/');
    }, [propertyId, isOwnerLoading, hasMyProperties, router]);

    const items = useMemo(() => query.data?.items ?? [], [query.data?.items]);
    const propertyTitle = propertyId != null ? propertyQuery.data?.property?.title : null;

    const handleReply = (reviewId: number, text: string) => {
        reply.mutate(
            { reviewId, text },
            {
                onSuccess: () => toast.success('Ответ сохранён'),
                onError: () => toast.error('Не удалось сохранить ответ'),
            },
        );
    };

    if (propertyId == null && !isOwnerLoading && !hasMyProperties) {
        return (
            <div className="flex items-center gap-2 text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" />
                Перенаправление…
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-foreground">
                    {propertyId != null ? 'Отзывы по объявлению' : 'Отзывы'}
                </h1>
                {propertyTitle ? (
                    <p className="text-sm text-muted-foreground mt-1">{propertyTitle}</p>
                ) : (
                    <p className="text-sm text-muted-foreground mt-1">Ответы на отзывы по вашим объявлениям</p>
                )}
                {propertyId != null ? (
                    <Button variant="link" className="px-0 h-auto mt-2" asChild>
                        <Link href="/kabinet/otzyvy/">Все отзывы</Link>
                    </Button>
                ) : null}
            </div>

            {propertyId == null ? <ReviewsSubnav active="incoming" hasMyProperties={hasMyProperties} /> : null}

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
                        <ReviewReplyCard
                            key={review.id}
                            review={review}
                            propertyId={propertyId}
                            onReply={handleReply}
                            isReplyPending={reply.isPending}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
