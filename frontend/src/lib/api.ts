import axios, { type AxiosError, type InternalAxiosRequestConfig } from 'axios';
import {
    getRefreshToken,
    getToken,
    redirectToLoginIfListingSessionExpired,
    removeToken,
    setToken,
} from '@/lib/auth';
import { refreshAuthTokens } from '@/lib/auth-refresh';

const api = axios.create({
    baseURL: process.env.NEXT_PUBLIC_API_URL,
    headers: {
        'Content-Type': 'application/json',
    },
});

let refreshPromise: Promise<string> | null = null;

const refreshAccessToken = async (): Promise<string> => {
    const refreshToken = getRefreshToken();
    if (!refreshToken) {
        throw new Error('Refresh token missing');
    }

    const pair = await refreshAuthTokens(refreshToken);
    setToken(pair.token, pair.refreshToken);
    return pair.token;
};

const shouldAttemptRefresh = (error: AxiosError, request: InternalAxiosRequestConfig & { _retry?: boolean }) => {
    if (request._retry) {
        return false;
    }
    if (error.response?.status !== 401) {
        return false;
    }
    const url = request.url ?? '';
    if (url.includes('/auth/refresh') || url.includes('/auth/login')) {
        return false;
    }
    return !!getRefreshToken();
};

api.interceptors.request.use(
    (config) => {
        const token = getToken();
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error),
);

api.interceptors.response.use(
    (response) => response,
    async (error: AxiosError) => {
        const request = error.config as (InternalAxiosRequestConfig & { _retry?: boolean }) | undefined;
        if (!request || !shouldAttemptRefresh(error, request)) {
            if (error.response?.status === 401) {
                removeToken();
                redirectToLoginIfListingSessionExpired();
            }
            return Promise.reject(error);
        }

        request._retry = true;

        try {
            if (!refreshPromise) {
                refreshPromise = refreshAccessToken().finally(() => {
                    refreshPromise = null;
                });
            }
            const token = await refreshPromise;
            request.headers.Authorization = `Bearer ${token}`;
            return api(request);
        } catch (refreshError) {
            removeToken();
            redirectToLoginIfListingSessionExpired();
            return Promise.reject(refreshError);
        }
    },
);

export default api;
