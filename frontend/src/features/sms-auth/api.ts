import api from '@/lib/api';

type SmsAuthResponse = {
    data: {
        token: string;
    };
};

export const requestSmsCode = async (phone: string): Promise<void> => {
    await api.post('/auth/sms/request', { phone });
};

export const verifySmsCode = async (phone: string, code: string): Promise<string> => {
    const response = await api.post<SmsAuthResponse>('/auth/sms/verify', { phone, code });
    return response.data.data.token;
};
