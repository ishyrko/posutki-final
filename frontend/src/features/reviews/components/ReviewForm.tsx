'use client';

import { useState } from 'react';
import Link from 'next/link';
import { Star } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { useSubmitReview } from '../hooks';
import { useUser } from '@/features/auth/hooks';
import { PhoneVerifyDialog } from '@/features/phones/components/PhoneVerifyDialog';
import { toast } from 'sonner';

type ReviewFormProps = {
    propertyId: number;
    onSubmitted?: () => void;
};

export function ReviewForm({ propertyId, onSubmitted }: ReviewFormProps) {
    const { data: user } = useUser();
    const [rating, setRating] = useState(0);
    const [hover, setHover] = useState(0);
    const [text, setText] = useState('');
    const [shareDataWithOwner, setShareDataWithOwner] = useState(true);
    const [phoneDialogOpen, setPhoneDialogOpen] = useState(false);
    const submit = useSubmitReview(propertyId);

    const displayRating = hover || rating;
    const hasVerifiedPhone = Boolean(user?.isPhoneVerified);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!hasVerifiedPhone) {
            toast.error('Подтвердите телефон, чтобы оставить отзыв');
            return;
        }
        if (rating < 1 || rating > 5) {
            toast.error('Выберите оценку от 1 до 5');
            return;
        }
        const trimmed = text.trim();
        if (!trimmed) {
            toast.error('Напишите текст отзыва');
            return;
        }
        submit.mutate(
            { rating, text: trimmed, shareDataWithOwner },
            {
                onSuccess: (data) => {
                    toast.success(data.message ?? 'Отзыв отправлен на модерацию');
                    setText('');
                    setRating(0);
                    setHover(0);
                    setShareDataWithOwner(true);
                    onSubmitted?.();
                },
                onError: (err: unknown) => {
                    const msg =
                        err && typeof err === 'object' && 'response' in err
                            ? (err as { response?: { data?: { error?: { message?: string } } } }).response?.data?.error
                                  ?.message
                            : undefined;
                    toast.error(msg ?? 'Не удалось отправить отзыв');
                },
            },
        );
    };

    return (
        <>
            <form onSubmit={handleSubmit} className="space-y-4 rounded-xl border border-border/60 bg-card/30 p-4">
                {!hasVerifiedPhone && (
                    <div className="rounded-lg border border-amber-500/30 bg-amber-500/5 px-4 py-3 text-sm text-foreground space-y-2">
                        <p>Чтобы оставить отзыв, подтвердите номер телефона в профиле.</p>
                        <div className="flex flex-wrap gap-2">
                            <Button type="button" size="sm" onClick={() => setPhoneDialogOpen(true)}>
                                Подтвердить телефон
                            </Button>
                            <Button type="button" variant="outline" size="sm" asChild>
                                <Link href="/kabinet/profil/">Профиль</Link>
                            </Button>
                        </div>
                    </div>
                )}

                <div>
                    <Label className="text-foreground mb-2 block">Ваша оценка</Label>
                    <div className="flex gap-1">
                        {[1, 2, 3, 4, 5].map((n) => (
                            <button
                                key={n}
                                type="button"
                                className="p-1 rounded transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                onMouseEnter={() => setHover(n)}
                                onMouseLeave={() => setHover(0)}
                                onClick={() => setRating(n)}
                                aria-label={`Оценка ${n} из 5`}
                            >
                                <Star
                                    className={`w-8 h-8 ${n <= displayRating ? 'fill-amber-400 text-amber-400' : 'text-muted-foreground/35'}`}
                                />
                            </button>
                        ))}
                    </div>
                </div>
                <div>
                    <Label htmlFor="review-text" className="text-foreground mb-2 block">
                        Комментарий
                    </Label>
                    <Textarea
                        id="review-text"
                        value={text}
                        onChange={(e) => setText(e.target.value)}
                        rows={4}
                        placeholder="Расскажите о своём опыте…"
                        className="resize-y min-h-[100px]"
                        required
                    />
                </div>

                <div className="space-y-2">
                    <div className="flex items-start gap-2">
                        <Checkbox
                            id="review-share-data"
                            checked={shareDataWithOwner}
                            onCheckedChange={(v) => setShareDataWithOwner(v === true)}
                            className="mt-0.5"
                        />
                        <label htmlFor="review-share-data" className="text-sm text-muted-foreground leading-relaxed cursor-pointer">
                            Разрешаю передать мои данные владельцу квартиры
                        </label>
                    </div>
                    <p className="text-xs text-muted-foreground pl-6">
                        Email и телефон не будут опубликованы на сайте.
                    </p>
                </div>

                <Button
                    type="submit"
                    className="bg-gradient-primary text-primary-foreground"
                    disabled={submit.isPending || !hasVerifiedPhone}
                >
                    {submit.isPending ? 'Отправка…' : 'Отправить отзыв'}
                </Button>
            </form>

            <PhoneVerifyDialog open={phoneDialogOpen} onOpenChange={setPhoneDialogOpen} />
        </>
    );
}
