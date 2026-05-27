'use client';

import { useMemo, useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { ru } from 'date-fns/locale';
import ReCAPTCHA from 'react-google-recaptcha';
import { CalendarIcon, CheckCircle, Minus, Plus } from 'lucide-react';
import { toast } from 'sonner';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { RecaptchaWidget } from '@/components/RecaptchaWidget';
import { cn } from '@/lib/utils';
import { useUser } from '@/features/auth/hooks';
import { formatAddress, Property } from '@/features/properties/types';
import { PriceDisplay } from '@/components/BynCurrency';
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from '@/features/properties/price-display';
import { useExchangeRates } from '@/features/properties/hooks';
import { useCurrency } from '@/context/CurrencyContext';
import {
    bookingInquirySchema,
    BookingInquiryFormData,
    useSubmitBookingInquiry,
} from '@/features/properties/booking-inquiry';

interface BookingInquiryModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    property: Property;
}

function formatDateLabel(value?: string): string {
    if (!value) return 'Дата';
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return 'Дата';
    return format(date, 'dd.MM.yyyy', { locale: ru });
}

export function BookingInquiryModal({ open, onOpenChange, property }: BookingInquiryModalProps) {
    const { data: user } = useUser();
    const { mutate: submitInquiry, isPending } = useSubmitBookingInquiry();
    const { data: exchangeRates } = useExchangeRates();
    const { currency } = useCurrency();
    const [submitted, setSubmitted] = useState(false);

    const siteKey = process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY?.trim() ?? '';
    const recaptchaRef = useRef<ReCAPTCHA | null>(null);
    const [recaptchaToken, setRecaptchaToken] = useState<string | null>(null);

    const rates = exchangeRates ?? DEFAULT_EXCHANGE_RATES_FALLBACK;
    const priceDisplay = useMemo(
        () => formatPropertyPrices(property, rates, currency),
        [property, rates, currency],
    );

    const previewImage = property.images[0]?.thumbnailUrl || property.images[0]?.url || null;
    const addressStr = formatAddress(property.address);

    const defaultValues = useMemo<BookingInquiryFormData>(() => ({
        propertyId: property.id,
        name: user ? `${user.firstName} ${user.lastName}`.trim() : '',
        phone: user?.phone ?? '+375',
        email: user?.email ?? '',
        guests: 2,
        checkIn: '',
        checkOut: '',
        notes: '',
    }), [property.id, user]);

    const form = useForm<BookingInquiryFormData>({
        resolver: zodResolver(bookingInquirySchema),
        defaultValues,
    });

    const onSubmit = (data: BookingInquiryFormData) => {
        if (siteKey && !recaptchaToken) {
            toast.error('Подтвердите, что вы не робот');
            return;
        }

        submitInquiry(
            { ...data, recaptchaToken: recaptchaToken ?? '' },
            {
                onSuccess: () => {
                    setSubmitted(true);
                    form.reset(defaultValues);
                    setRecaptchaToken(null);
                    recaptchaRef.current?.reset();
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {open ? (
                <DialogContent className="sm:max-w-3xl max-h-[90vh] overflow-y-auto p-0 gap-0">
                    <DialogHeader className="px-6 pt-6 pb-4 border-b border-border">
                        <DialogTitle className="text-center text-xl font-display">
                            Забронировать квартиру
                        </DialogTitle>
                    </DialogHeader>

                    {submitted ? (
                        <div className="flex flex-col items-center gap-3 px-6 py-12 text-center">
                            <CheckCircle className="w-12 h-12 text-primary" />
                            <p className="text-lg font-medium text-foreground">Заявка отправлена!</p>
                            <p className="text-sm text-muted-foreground max-w-sm">
                                Владелец получит уведомление на почту и увидит заявку в личном кабинете.
                            </p>
                            <Button
                                className="mt-2 bg-gradient-primary text-primary-foreground border-0"
                                onClick={() => onOpenChange(false)}
                            >
                                Закрыть
                            </Button>
                        </div>
                    ) : (
                        <div className="grid md:grid-cols-[1fr_240px]">
                            <div className="px-6 py-5">
                                <Form {...form}>
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            void form.handleSubmit(onSubmit)(e);
                                        }}
                                        className="space-y-4"
                                    >
                                        <FormField
                                            control={form.control}
                                            name="name"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Имя *</FormLabel>
                                                    <FormControl>
                                                        <Input placeholder="Введите Ваше имя" {...field} />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <FormField
                                                control={form.control}
                                                name="phone"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Телефон *</FormLabel>
                                                        <FormControl>
                                                            <Input placeholder="+375" {...field} />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                            <FormField
                                                control={form.control}
                                                name="email"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>E-mail</FormLabel>
                                                        <FormControl>
                                                            <Input
                                                                type="email"
                                                                placeholder="example@mail.ru"
                                                                {...field}
                                                            />
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />
                                        </div>

                                        <div className="space-y-4">
                                            <FormField
                                                control={form.control}
                                                name="guests"
                                                render={({ field }) => (
                                                    <FormItem>
                                                        <FormLabel>Количество гостей</FormLabel>
                                                        <FormControl>
                                                            <div className="flex h-10 w-[8.75rem] items-stretch overflow-hidden rounded-md border border-input bg-background">
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-full w-9 shrink-0 rounded-none"
                                                                    onClick={() => field.onChange(Math.max(1, (field.value ?? 1) - 1))}
                                                                >
                                                                    <Minus className="w-4 h-4" />
                                                                </Button>
                                                                <span className="flex flex-1 items-center justify-center text-sm font-medium tabular-nums">
                                                                    {field.value ?? 1}
                                                                </span>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-full w-9 shrink-0 rounded-none"
                                                                    onClick={() => field.onChange(Math.min(50, (field.value ?? 1) + 1))}
                                                                >
                                                                    <Plus className="w-4 h-4" />
                                                                </Button>
                                                            </div>
                                                        </FormControl>
                                                        <FormMessage />
                                                    </FormItem>
                                                )}
                                            />

                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <FormField
                                                    control={form.control}
                                                    name="checkIn"
                                                    render={({ field }) => (
                                                        <FormItem>
                                                            <FormLabel>Дата заезда</FormLabel>
                                                            <Popover>
                                                                <PopoverTrigger asChild>
                                                                    <FormControl>
                                                                        <Button
                                                                            type="button"
                                                                            variant="outline"
                                                                            className={cn(
                                                                                'w-full min-w-0 justify-start gap-2 px-3 text-left font-normal h-10',
                                                                                !field.value && 'text-muted-foreground',
                                                                            )}
                                                                        >
                                                                            <CalendarIcon className="h-4 w-4 shrink-0" />
                                                                            <span className="truncate">{formatDateLabel(field.value)}</span>
                                                                        </Button>
                                                                    </FormControl>
                                                                </PopoverTrigger>
                                                                <PopoverContent className="w-auto p-0" align="start">
                                                                    <Calendar
                                                                        mode="single"
                                                                        locale={ru}
                                                                        selected={field.value ? new Date(`${field.value}T00:00:00`) : undefined}
                                                                        onSelect={(date) => field.onChange(date ? format(date, 'yyyy-MM-dd') : '')}
                                                                        disabled={(date) => date < new Date(new Date().setHours(0, 0, 0, 0))}
                                                                        initialFocus
                                                                    />
                                                                </PopoverContent>
                                                            </Popover>
                                                            <FormMessage />
                                                        </FormItem>
                                                    )}
                                                />

                                                <FormField
                                                    control={form.control}
                                                    name="checkOut"
                                                    render={({ field }) => (
                                                        <FormItem>
                                                            <FormLabel>Дата выезда</FormLabel>
                                                            <Popover>
                                                                <PopoverTrigger asChild>
                                                                    <FormControl>
                                                                        <Button
                                                                            type="button"
                                                                            variant="outline"
                                                                            className={cn(
                                                                                'w-full min-w-0 justify-start gap-2 px-3 text-left font-normal h-10',
                                                                                !field.value && 'text-muted-foreground',
                                                                            )}
                                                                        >
                                                                            <CalendarIcon className="h-4 w-4 shrink-0" />
                                                                            <span className="truncate">{formatDateLabel(field.value)}</span>
                                                                        </Button>
                                                                    </FormControl>
                                                                </PopoverTrigger>
                                                                <PopoverContent className="w-auto p-0" align="start">
                                                                    <Calendar
                                                                        mode="single"
                                                                        locale={ru}
                                                                        selected={field.value ? new Date(`${field.value}T00:00:00`) : undefined}
                                                                        onSelect={(date) => field.onChange(date ? format(date, 'yyyy-MM-dd') : '')}
                                                                        disabled={(date) => {
                                                                            const today = new Date(new Date().setHours(0, 0, 0, 0));
                                                                            const checkIn = form.getValues('checkIn');
                                                                            const minDate = checkIn
                                                                                ? new Date(`${checkIn}T00:00:00`)
                                                                                : today;
                                                                            return date < minDate;
                                                                        }}
                                                                        initialFocus
                                                                    />
                                                                </PopoverContent>
                                                            </Popover>
                                                            <FormMessage />
                                                        </FormItem>
                                                    )}
                                                />
                                            </div>
                                        </div>

                                        <FormField
                                            control={form.control}
                                            name="notes"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>Дополнительно</FormLabel>
                                                    <FormControl>
                                                        <Textarea
                                                            placeholder="Дополнительно"
                                                            className="min-h-[90px] resize-none"
                                                            maxLength={1000}
                                                            {...field}
                                                        />
                                                    </FormControl>
                                                    <FormMessage />
                                                </FormItem>
                                            )}
                                        />

                                        {siteKey ? (
                                            <RecaptchaWidget
                                                recaptchaRef={recaptchaRef}
                                                onChange={setRecaptchaToken}
                                                onExpired={() => setRecaptchaToken(null)}
                                            />
                                        ) : null}

                                        <Button
                                            type="submit"
                                            disabled={isPending}
                                            className="w-full h-11 bg-gradient-primary text-primary-foreground border-0"
                                        >
                                            {isPending ? 'Отправка…' : 'Отправить заявку'}
                                        </Button>
                                    </form>
                                </Form>
                            </div>

                            <aside className="hidden md:flex flex-col gap-4 bg-muted/40 border-l border-border px-4 py-5">
                                {previewImage ? (
                                    // eslint-disable-next-line @next/next/no-img-element
                                    <img
                                        src={previewImage}
                                        alt={property.title}
                                        className="w-full h-28 object-cover rounded-lg"
                                    />
                                ) : (
                                    <div className="w-full h-28 rounded-lg bg-muted" />
                                )}
                                <div className="space-y-2">
                                    <p className="text-sm text-foreground leading-snug">{addressStr}</p>
                                    <div className="inline-flex items-center rounded-md bg-emerald-100 px-2.5 py-1 text-sm font-semibold text-emerald-800">
                                        <PriceDisplay
                                            amount={priceDisplay.primaryAmount}
                                            currency={priceDisplay.primaryCurrency}
                                        />
                                    </div>
                                </div>
                            </aside>
                        </div>
                    )}
                </DialogContent>
            ) : null}
        </Dialog>
    );
}
