import api from '@/lib/api';
import { LoginResponse, RegisterResponse, User } from './types';
import { z } from 'zod';

export const loginSchema = z.object({
    email: z.string().email('Введите корректный email'),
    password: z.string().min(6, 'Пароль не короче 6 символов'),
});

export type LoginCredentials = z.infer<typeof loginSchema>;

export const registerSchema = z.object({
    email: z.string().email('Введите корректный email'),
    password: z.string().min(6, 'Пароль не короче 6 символов'),
    firstName: z.string().min(2, 'Укажите имя (не короче 2 символов)'),
    lastName: z.string().min(2, 'Укажите фамилию (не короче 2 символов)'),
    phone: z.string().optional(),
});

export type RegisterCredentials = z.infer<typeof registerSchema>;

export const login = async (credentials: LoginCredentials): Promise<LoginResponse> => {
    const response = await api.post<LoginResponse>('/auth/login', credentials);
    return response.data;
};

export const register = async (credentials: RegisterCredentials): Promise<RegisterResponse> => {
    const response = await api.post<RegisterResponse>('/register', credentials);
    return response.data;
};

export const resetPasswordSchema = z.object({
    email: z.string().email('Введите корректный email'),
});

export type ResetPasswordData = z.infer<typeof resetPasswordSchema>;

export const resetPassword = async (data: ResetPasswordData): Promise<void> => {
    await api.post('/auth/reset-password', data);
};

export const confirmResetPasswordSchema = z.object({
    email: z.string().email(),
    token: z.string().min(1),
    password: z.string().min(6, 'Пароль должен быть не менее 6 символов'),
    passwordConfirm: z.string().min(6, 'Подтвердите пароль'),
}).refine((data) => data.password === data.passwordConfirm, {
    message: 'Пароли не совпадают',
    path: ['passwordConfirm'],
});

export type ConfirmResetPasswordData = z.infer<typeof confirmResetPasswordSchema>;

export const confirmResetPassword = async (data: { email: string; token: string; password: string }): Promise<void> => {
    await api.post('/auth/reset-password/confirm', data);
};

export const googleLogin = async (idToken: string): Promise<LoginResponse> => {
    const response = await api.post<{ data: { token: string } }>('/auth/google', { idToken });
    return { token: response.data.data.token };
};

export const getMe = async (): Promise<User> => {
    const response = await api.get<{ data: User }>('/auth/me');
    return response.data.data;
};

export const verifyEmail = async (data: { email: string; token: string }): Promise<void> => {
    await api.post('/auth/verify-email', data);
};

export const resendVerification = async (email: string): Promise<void> => {
    await api.post('/auth/resend-verification', { email });
};

export const updateEmail = async (email: string): Promise<void> => {
    await api.post('/users/update-email', { email });
};

export const confirmEmailChange = async (data: { email: string; token: string }): Promise<void> => {
    await api.post('/auth/confirm-email-change', data);
};
