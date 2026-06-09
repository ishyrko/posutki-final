import axios from 'axios';
import { AUTH_REFRESH_TOKEN_KEY } from '@/lib/auth-constants';

export interface AuthTokenPair {
    token: string;
    refreshToken: string;
}

const refreshClient = axios.create({
    baseURL: process.env.NEXT_PUBLIC_API_URL,
    headers: {
        'Content-Type': 'application/json',
    },
});

export const readRefreshToken = (): string | null => {
    if (typeof window === 'undefined') {
        return null;
    }
    return localStorage.getItem(AUTH_REFRESH_TOKEN_KEY);
};

export const refreshAuthTokens = async (refreshToken: string): Promise<AuthTokenPair> => {
    const response = await refreshClient.post<{
        data?: AuthTokenPair;
        token?: string;
        refreshToken?: string;
    }>('/auth/refresh', { refreshToken });

    const payload = response.data;
    const token = payload.data?.token ?? payload.token;
    const nextRefreshToken = payload.data?.refreshToken ?? payload.refreshToken;

    if (!token || !nextRefreshToken) {
        throw new Error('Некорректный ответ при обновлении токена');
    }

    return { token, refreshToken: nextRefreshToken };
};
