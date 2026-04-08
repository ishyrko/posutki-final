import api from '@/lib/api';
import { z } from 'zod';
import { User } from '@/features/auth/types';

export const updateProfileSchema = z.object({
    firstName: z.string().min(2, 'Введите имя'),
    lastName: z.string().min(2, 'Введите фамилию'),
    phone: z.string().optional(),
    avatar: z.string().optional(),
});

export type UpdateProfileData = z.infer<typeof updateProfileSchema>;

export const changePasswordSchema = z.object({
    currentPassword: z.string().min(1, 'Введите текущий пароль'),
    newPassword: z.string().min(6, 'Минимум 6 символов'),
    confirmPassword: z.string().min(6, 'Минимум 6 символов'),
}).refine(data => data.newPassword === data.confirmPassword, {
    message: 'Пароли не совпадают',
    path: ['confirmPassword'],
});

export type ChangePasswordData = z.infer<typeof changePasswordSchema>;

export const updateProfile = async (data: UpdateProfileData): Promise<User> => {
    const response = await api.put<User>('/users/profile', data);
    return response.data;
};

export const changePassword = async (data: Omit<ChangePasswordData, 'confirmPassword'>): Promise<void> => {
    await api.post('/users/change-password', data);
};
