'use client';

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { z } from 'zod';
import { toast } from 'sonner';
import api from '@/lib/api';
import { isAuthenticated } from '@/lib/auth';

export type BookingInquiryStatus = 'new' | 'replied' | 'accepted' | 'declined';

export function createBookingInquirySchema(requireEmail: boolean) {
    return z.object({
        propertyId: z.number().int().positive(),
        name: z.string().trim().min(2, 'Введите имя'),
        phone: z.string().trim().min(5, 'Введите телефон'),
        email: requireEmail
            ? z.string().trim().min(1, 'Укажите email').email('Введите корректный email')
            : z.union([z.literal(''), z.string().email('Введите корректный email')]).optional(),
        guests: z.number().int().min(1).max(50).optional(),
        checkIn: z.string().optional(),
        checkOut: z.string().optional(),
        notes: z.string().max(1000).optional(),
    });
}

export const bookingInquirySchema = createBookingInquirySchema(false);

export type BookingInquiryFormData = z.infer<typeof bookingInquirySchema>;

export type BookingInquiryPayload = BookingInquiryFormData & {
    recaptchaToken: string;
};

export interface BookingInquiryReply {
    text: string;
    repliedAt?: string | null;
}

export interface BookingInquiryItem {
    id: string;
    propertyId: string;
    propertyTitle?: string | null;
    propertyType?: string | null;
    propertyDealType?: string | null;
    propertyCitySlug?: string | null;
    propertyRegionName?: string | null;
    propertyImage?: string | null;
    propertyAddress?: string | null;
    propertyPriceAmount?: number | null;
    propertyPriceCurrency?: string | null;
    userId?: string | null;
    name: string;
    phone: string;
    email?: string | null;
    guests?: number | null;
    checkIn?: string | null;
    checkOut?: string | null;
    notes?: string | null;
    createdAt: string;
    isRead: boolean;
    status: BookingInquiryStatus;
    ownerReply?: string | null;
    repliedAt?: string | null;
    ownerReplies?: BookingInquiryReply[];
    availabilityBlockId?: string | null;
    canAccept?: boolean;
    canReply?: boolean;
}

export interface BookingInquiryListResponse {
    data: BookingInquiryItem[];
    pagination: {
        total: number;
        page: number;
        limit: number;
        pages: number;
    };
}

export function getErrorMessage(error: unknown, fallback: string): string {
    if (typeof error !== 'object' || error === null || !('response' in error)) {
        return fallback;
    }

    const response = (error as { response?: { data?: unknown } }).response;
    const data = response?.data;
    if (typeof data !== 'object' || data === null) {
        return fallback;
    }

    const nestedError = (data as { error?: { message?: unknown } }).error?.message;
    if (typeof nestedError === 'string' && nestedError.length > 0) {
        return nestedError;
    }

    const message = (data as { message?: unknown }).message;
    return typeof message === 'string' && message.length > 0 ? message : fallback;
}

export const submitBookingInquiry = async (
    data: BookingInquiryPayload,
): Promise<{ id: string }> => {
    const { recaptchaToken, ...formData } = data;
    const response = await api.post('/booking-inquiry', {
        ...formData,
        email: formData.email?.trim() ? formData.email.trim() : undefined,
        checkIn: formData.checkIn?.trim() ? formData.checkIn : undefined,
        checkOut: formData.checkOut?.trim() ? formData.checkOut : undefined,
        notes: formData.notes?.trim() ? formData.notes : undefined,
        recaptchaToken,
    });
    return response.data.data;
};

export const getMyBookingInquiries = async (
    page = 1,
    limit = 20,
): Promise<BookingInquiryListResponse> => {
    const response = await api.get('/booking-inquiries', {
        params: { page, limit },
    });
    return response.data;
};

export const getUnreadBookingInquiryCount = async (): Promise<number> => {
    const response = await api.get('/booking-inquiries/unread-count');
    return response.data.data.unreadCount;
};

export const markBookingInquiriesRead = async (): Promise<void> => {
    await api.post('/booking-inquiries/mark-read');
};

export const replyToBookingInquiry = async (
    inquiryId: string,
    text: string,
): Promise<void> => {
    await api.post(`/booking-inquiries/${inquiryId}/reply`, { text });
};

export const acceptBookingInquiry = async (
    inquiryId: string,
    options: { bookCalendar?: boolean } = {},
): Promise<void> => {
    await api.post(`/booking-inquiries/${inquiryId}/accept`, {
        bookCalendar: options.bookCalendar ?? true,
    });
};

export const declineBookingInquiry = async (inquiryId: string): Promise<void> => {
    await api.post(`/booking-inquiries/${inquiryId}/decline`);
};

export const useSubmitBookingInquiry = () => {
    return useMutation({
        mutationFn: submitBookingInquiry,
        onSuccess: () => {
            toast.success('Заявка отправлена. Владелец свяжется с вами.');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось отправить заявку'));
        },
    });
};

export const useMyBookingInquiries = (page = 1, limit = 20) => {
    return useQuery({
        queryKey: ['booking-inquiries', page, limit],
        queryFn: () => getMyBookingInquiries(page, limit),
        enabled: isAuthenticated(),
    });
};

export const useUnreadBookingInquiryCount = () => {
    return useQuery({
        queryKey: ['booking-inquiries-unread-count'],
        queryFn: getUnreadBookingInquiryCount,
        enabled: isAuthenticated(),
        refetchInterval: 30000,
    });
};

export const useMarkBookingInquiriesRead = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: markBookingInquiriesRead,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['booking-inquiries-unread-count'] });
        },
    });
};

export const useReplyToBookingInquiry = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ inquiryId, text }: { inquiryId: string; text: string }) =>
            replyToBookingInquiry(inquiryId, text),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['booking-inquiries'] });
            toast.success('Ответ отправлен гостю');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось отправить ответ'));
        },
    });
};

export const useAcceptBookingInquiry = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({
            inquiryId,
            propertyId,
            bookCalendar = true,
        }: {
            inquiryId: string;
            propertyId: string;
            bookCalendar?: boolean;
        }) => acceptBookingInquiry(inquiryId, { bookCalendar }).then(() => ({ propertyId, bookCalendar })),
        onSuccess: ({ propertyId, bookCalendar }) => {
            queryClient.invalidateQueries({ queryKey: ['booking-inquiries'] });
            queryClient.invalidateQueries({ queryKey: ['owner-calendar', Number(propertyId)] });
            queryClient.invalidateQueries({ queryKey: ['property-calendar', Number(propertyId)] });
            toast.success(
                bookCalendar
                    ? 'Заявка принята, даты отмечены в календаре'
                    : 'Заявка принята',
            );
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось принять заявку'));
        },
    });
};

export const useDeclineBookingInquiry = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: ({ inquiryId, propertyId }: { inquiryId: string; propertyId: string }) =>
            declineBookingInquiry(inquiryId).then(() => propertyId),
        onSuccess: (propertyId) => {
            queryClient.invalidateQueries({ queryKey: ['booking-inquiries'] });
            queryClient.invalidateQueries({ queryKey: ['owner-calendar', Number(propertyId)] });
            queryClient.invalidateQueries({ queryKey: ['property-calendar', Number(propertyId)] });
            toast.success('Заявка отклонена');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось отклонить заявку'));
        },
    });
};

export function getBookingInquiryStatusLabel(status: BookingInquiryStatus): string {
    switch (status) {
        case 'new':
            return 'Новая';
        case 'replied':
            return 'Отвечено';
        case 'accepted':
            return 'Принята';
        case 'declined':
            return 'Отклонена';
        default:
            return status;
    }
}

export function buildAcceptInquiryMessage(inquiry: Pick<BookingInquiryItem, 'name' | 'checkIn' | 'checkOut'>): string {
    const dates = formatInquiryDateRange(inquiry.checkIn, inquiry.checkOut);
    const greeting = inquiry.name?.trim() ? `Здравствуйте, ${inquiry.name.trim()}!` : 'Здравствуйте!';
    if (dates) {
        return `${greeting}\n\nПодтверждаю бронирование на ${dates}. Ждём вас!`;
    }
    return `${greeting}\n\nПодтверждаю бронирование. Ждём вас!`;
}

export function buildDeclineInquiryMessage(inquiry: Pick<BookingInquiryItem, 'name' | 'checkIn' | 'checkOut'>): string {
    const dates = formatInquiryDateRange(inquiry.checkIn, inquiry.checkOut);
    const greeting = inquiry.name?.trim() ? `Здравствуйте, ${inquiry.name.trim()}!` : 'Здравствуйте!';
    if (dates) {
        return `${greeting}\n\nК сожалению, на ${dates} объект недоступен.`;
    }
    return `${greeting}\n\nК сожалению, сейчас не можем подтвердить бронирование.`;
}

function formatInquiryDateRange(checkIn?: string | null, checkOut?: string | null): string | null {
    const from = formatInquiryDate(checkIn);
    const to = formatInquiryDate(checkOut);
    if (from && to) return `${from} — ${to}`;
    if (from) return `заезд ${from}`;
    return null;
}

function formatInquiryDate(value?: string | null): string | null {
    if (!value) return null;
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return null;
    return date.toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
