import api from '@/lib/api';
import { z } from 'zod';

export const submitFeedbackSchema = z.object({
    name: z.string().min(2, 'Введите имя'),
    email: z.string().email('Введите корректный email'),
    subject: z.string().min(3, 'Укажите тему'),
    message: z.string().min(10, 'Сообщение должно быть не короче 10 символов'),
});

export type SubmitFeedbackData = z.infer<typeof submitFeedbackSchema>;

export type SubmitFeedbackPayload = SubmitFeedbackData & {
    recaptchaToken: string;
};

type SubmitFeedbackResponse = {
    id: number;
};

export const submitFeedback = async (data: SubmitFeedbackPayload): Promise<SubmitFeedbackResponse> => {
    const response = await api.post('/contact', data);
    return response.data.data;
};
