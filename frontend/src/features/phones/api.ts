import api from '@/lib/api';
import { UserPhone } from './types';

export const getPhones = async (): Promise<UserPhone[]> => {
    const response = await api.get<{ data: UserPhone[] }>('/phones');
    return response.data.data;
};

export const requestVerification = async (phone: string): Promise<void> => {
    await api.post('/phones/request', { phone });
};

export const verifyPhone = async (phone: string, code: string): Promise<void> => {
    await api.post('/phones/verify', { phone, code });
};

export const deletePhone = async (id: number): Promise<void> => {
    await api.delete(`/phones/${id}`);
};
