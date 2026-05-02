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

/** Совпадает с backend UpdateUserIndividualProfileRequest */
export const individualProfileSchema = z.object({
    lastName: z.string().min(2, 'Фамилия не короче 2 символов').max(100),
    firstName: z.string().min(2, 'Имя не короче 2 символов').max(100),
    middleName: z.string().max(100).optional(),
    unp: z
        .string()
        .regex(/^[0-9A-Za-z]{9}$/, 'УНП: 9 символов (цифры и латинские буквы A–Z)'),
});

/** Совпадает с backend UpdateUserBusinessProfileRequest */
export const businessProfileSchema = z.object({
    organizationName: z.string().min(2, 'Наименование не короче 2 символов').max(255),
    contactName: z.string().min(2, 'Имя контакта не короче 2 символов').max(200),
    unp: z
        .string()
        .regex(/^[0-9A-Za-z]{9}$/, 'УНП: 9 символов (цифры и латинские буквы A–Z)'),
});

export type IndividualProfileFormData = z.infer<typeof individualProfileSchema>;
export type BusinessProfileFormData = z.infer<typeof businessProfileSchema>;

export const updateIndividualProfile = async (data: IndividualProfileFormData): Promise<void> => {
    const payload = {
        ...data,
        middleName: data.middleName?.trim() ? data.middleName.trim() : null,
    };
    await api.put('/users/profile/individual', payload);
};

export const updateBusinessProfile = async (data: BusinessProfileFormData): Promise<void> => {
    await api.put('/users/profile/business', data);
};

export const updateProfile = async (data: UpdateProfileData): Promise<User> => {
    const response = await api.put<User>('/users/profile', data);
    return response.data;
};

export const changePassword = async (data: Omit<ChangePasswordData, 'confirmPassword'>): Promise<void> => {
    await api.post('/users/change-password', data);
};
