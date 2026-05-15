'use client';

import { useState } from 'react';
import { Star } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useSubmitReview } from '../hooks';
import { toast } from 'sonner';

type ReviewFormProps = {
    propertyId: number;
};

export function ReviewForm({ propertyId }: ReviewFormProps) {
    const [rating, setRating] = useState(0);
    const [hover, setHover] = useState(0);
    const [text, setText] = useState('');
    const submit = useSubmitReview(propertyId);

    const displayRating = hover || rating;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (rating < 1 || rating > 5) {
            toast.error('Выберите оценку от 1 до 5');
            return;
        }
        submit.mutate(
            { rating, text: text.trim() || null },
            {
                onSuccess: (data) => {
                    toast.success(data.message ?? 'Отзыв отправлен на модерацию');
                    setText('');
                    setRating(0);
                    setHover(0);
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
        <form onSubmit={handleSubmit} className="space-y-4 rounded-xl border border-border/60 bg-card/30 p-4">
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
                    Комментарий (необязательно)
                </Label>
                <Textarea
                    id="review-text"
                    value={text}
                    onChange={(e) => setText(e.target.value)}
                    rows={4}
                    placeholder="Расскажите о своём опыте…"
                    className="resize-y min-h-[100px]"
                />
            </div>
            <Button type="submit" className="bg-gradient-primary text-primary-foreground" disabled={submit.isPending}>
                {submit.isPending ? 'Отправка…' : 'Отправить отзыв'}
            </Button>
        </form>
    );
}
