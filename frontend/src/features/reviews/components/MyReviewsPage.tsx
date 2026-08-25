'use client';

import Link from 'next/link';
import { useState } from 'react';
import { Loader2, Star, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { buildPropertyUrlFromRegionName } from '@/features/catalog/slugs';
import { useHasMyProperties } from '@/features/properties/hooks';
import { useDeleteReview, useMyReviews } from '../hooks';
import type { MyReviewItem, ReviewStatus } from '../types';
import { ReviewsSubnav } from './ReviewsSubnav';
import { toast } from 'sonner';

const STATUS_LABELS: Record<Exclude<ReviewStatus, 'deleted'>, { label: string; className: string }> = {
    pending: { label: 'На модерации', className: 'bg-amber-100 text-amber-800' },
    approved: { label: 'Опубликован', className: 'bg-green-100 text-green-800' },
    rejected: { label: 'Отклонён', className: 'bg-red-100 text-red-800' },
};

function MyReviewCard({ review }: { review: MyReviewItem }) {
    const deleteReview = useDeleteReview(review.property.id);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const statusConfig = STATUS_LABELS[review.status as Exclude<ReviewStatus, 'deleted'>];
    const propertyHref = buildPropertyUrlFromRegionName(review.property.type, review.property.id);

    const handleDelete = () => {
        deleteReview.mutate(review.id, {
            onSuccess: () => {
                setDeleteOpen(false);
                toast.success('Отзыв удалён');
            },
            onError: () => toast.error('Не удалось удалить отзыв'),
        });
    };

    return (
        <article className="rounded-xl border border-border/60 bg-card/50 p-4 shadow-sm space-y-3">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="space-y-1 min-w-0">
                    <Link href={propertyHref} className="text-sm font-medium text-primary hover:underline line-clamp-2">
                        {review.property.title}
                    </Link>
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
                </div>
                {statusConfig ? (
                    <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs font-medium ${statusConfig.className}`}>
                        {statusConfig.label}
                    </span>
                ) : null}
            </div>

            {review.text ? (
                <p className="text-sm text-muted-foreground whitespace-pre-line">{review.text}</p>
            ) : null}

            {review.status === 'rejected' && review.moderationComment ? (
                <p className="text-sm text-destructive/90 rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-2">
                    {review.moderationComment}
                </p>
            ) : null}

            {review.status === 'approved' && review.ownerReply ? (
                <div className="rounded-lg border border-border/60 bg-muted/30 px-3 py-2 space-y-1">
                    <p className="text-xs font-medium text-foreground">Ответ владельца</p>
                    <p className="text-sm text-muted-foreground whitespace-pre-line">{review.ownerReply}</p>
                    {review.ownerRepliedAt ? (
                        <p className="text-xs text-muted-foreground">
                            {new Date(review.ownerRepliedAt).toLocaleDateString('ru-RU', {
                                day: 'numeric',
                                month: 'long',
                                year: 'numeric',
                            })}
                        </p>
                    ) : null}
                </div>
            ) : null}

            <div className="pt-1">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    disabled={deleteReview.isPending}
                    onClick={() => setDeleteOpen(true)}
                >
                    <Trash2 className="w-3.5 h-3.5 mr-1.5" />
                    Удалить отзыв
                </Button>
            </div>

            <AlertDialog open={deleteOpen} onOpenChange={setDeleteOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Удалить отзыв?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Отзыв будет удалён. После этого вы сможете оставить новый отзыв об этом объявлении.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Отмена</AlertDialogCancel>
                        <AlertDialogAction
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            disabled={deleteReview.isPending}
                            onClick={handleDelete}
                        >
                            {deleteReview.isPending ? 'Удаление…' : 'Удалить'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </article>
    );
}

export function MyReviewsPage() {
    const { hasMyProperties } = useHasMyProperties();
    const query = useMyReviews();
    const items = query.data?.items ?? [];

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-foreground">Отзывы</h1>
                <p className="text-sm text-muted-foreground mt-1">Отзывы, которые вы оставили на объявления</p>
            </div>

            <ReviewsSubnav active="mine" hasMyProperties={hasMyProperties} />

            {query.isLoading ? (
                <div className="flex items-center gap-2 text-muted-foreground">
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Загрузка…
                </div>
            ) : items.length === 0 ? (
                <p className="text-sm text-muted-foreground">Вы ещё не оставляли отзывов.</p>
            ) : (
                <div className="space-y-4">
                    {items.map((review) => (
                        <MyReviewCard key={review.id} review={review} />
                    ))}
                </div>
            )}
        </div>
    );
}
