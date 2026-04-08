'use client';

import { useCallback, useEffect, useRef } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { login, register, googleLogin, getMe, resetPassword, confirmResetPassword, LoginCredentials, RegisterCredentials, ResetPasswordData } from './api';
import { setToken, removeToken, isAuthenticated } from '@/lib/auth';
import { useRouter } from 'next/navigation';
import { toast } from 'sonner';

function getErrorMessage(error: unknown, fallback: string): string {
    if (typeof error !== 'object' || error === null || !('response' in error)) {
        return fallback;
    }

    const response = (error as { response?: { data?: unknown } }).response;
    const data = response?.data;
    if (typeof data !== 'object' || data === null) {
        return fallback;
    }

    const message = (data as { message?: unknown }).message;
    if (typeof message === 'string' && message.length > 0) {
        return message;
    }

    const nestedError = (data as { error?: { message?: unknown } }).error?.message;
    if (typeof nestedError === 'string' && nestedError.length > 0) {
        return nestedError;
    }

    return fallback;
}

export const useLogin = (redirectAfter?: string) => {
    const router = useRouter();
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: (credentials: LoginCredentials) => login(credentials),
        onSuccess: (data) => {
            setToken(data.token);
            queryClient.invalidateQueries({ queryKey: ['me'] });
            toast.success('Вы успешно вошли');
            router.push(redirectAfter ?? '/kabinet/');
        },
        onError: (error: unknown, variables: LoginCredentials) => {
            const msg = getErrorMessage(error, 'Не удалось войти');
            if (msg.includes('не подтвержд') || msg.toLowerCase().includes('verify')) {
                const pendingUrl = `/verify-email-pending?email=${encodeURIComponent(variables.email)}`;
                toast.error(msg, {
                    action: {
                        label: 'Подтвердить email',
                        onClick: () => {
                            window.location.href = pendingUrl;
                        },
                    },
                });
            } else {
                toast.error(msg);
            }
        },
    });
};

export const useRegister = () => {
    const router = useRouter();

    return useMutation({
        mutationFn: (credentials: RegisterCredentials) => register(credentials),
        onSuccess: (_data, variables) => {
            toast.success('Регистрация успешна. Проверьте почту и подтвердите email.');
            router.push(`/verify-email-pending?email=${encodeURIComponent(variables.email)}`);
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось зарегистрироваться'));
        },
    });
};

export const useResetPassword = () => {
    return useMutation({
        mutationFn: (data: ResetPasswordData) => resetPassword(data),
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось отправить письмо'));
        },
    });
};

export const useConfirmResetPassword = () => {
    const router = useRouter();

    return useMutation({
        mutationFn: (data: { email: string; token: string; password: string }) => confirmResetPassword(data),
        onSuccess: () => {
            toast.success('Пароль успешно изменён');
            router.push('/login');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось сбросить пароль. Ссылка недействительна или просрочена.'));
        },
    });
};

export const useGoogleLogin = (redirectAfter?: string) => {
    const router = useRouter();
    const queryClient = useQueryClient();
    const hiddenBtnRef = useRef<HTMLDivElement | null>(null);
    const initializedRef = useRef(false);
    const isPending = useRef(false);

    const { mutateAsync } = useMutation({
        mutationFn: (idToken: string) => googleLogin(idToken),
        onSuccess: (data) => {
            setToken(data.token);
            queryClient.invalidateQueries({ queryKey: ['me'] });
            toast.success('Вход через Google выполнен');
            router.push(redirectAfter ?? '/kabinet/');
        },
        onError: (error: unknown) => {
            toast.error(getErrorMessage(error, 'Не удалось войти через Google'));
        },
    });

    useEffect(() => {
        if (document.getElementById('gsi-script')) return;
        const script = document.createElement('script');
        script.id = 'gsi-script';
        script.src = 'https://accounts.google.com/gsi/client';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }, []);

    const ensureInitialized = useCallback(() => {
        const clientId = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID;
        if (!clientId || clientId === 'YOUR_GOOGLE_CLIENT_ID_HERE') {
            toast.error('Google Client ID не настроен');
            return false;
        }
        if (!window.google?.accounts?.id) {
            toast.error('Google SDK загружается, попробуйте через секунду');
            return false;
        }
        if (initializedRef.current) return true;

        window.google.accounts.id.initialize({
            client_id: clientId,
            callback: async (response: { credential: string }) => {
                if (isPending.current) return;
                isPending.current = true;
                try {
                    await mutateAsync(response.credential);
                } finally {
                    isPending.current = false;
                }
            },
        } as google.accounts.IdConfiguration);

        if (!hiddenBtnRef.current) {
            const div = document.createElement('div');
            div.style.position = 'absolute';
            div.style.width = '0';
            div.style.height = '0';
            div.style.overflow = 'hidden';
            document.body.appendChild(div);
            hiddenBtnRef.current = div;
        }

        window.google.accounts.id.renderButton(hiddenBtnRef.current, {
            type: 'standard',
            size: 'large',
        });

        initializedRef.current = true;
        return true;
    }, [mutateAsync]);

    const trigger = useCallback(() => {
        if (!ensureInitialized()) return;
        const btn = hiddenBtnRef.current?.querySelector<HTMLElement>('[role="button"], iframe');
        if (btn) {
            btn.click();
        } else {
            window.google?.accounts?.id?.prompt();
        }
    }, [ensureInitialized]);

    return { trigger };
};

export const useUser = () => {
    return useQuery({
        queryKey: ['me'],
        queryFn: getMe,
        enabled: isAuthenticated(),
        // One retry reduces blank redirect flashes from transient network errors on /kabinet.
        retry: 1,
    });
};

export const useLogout = () => {
    const router = useRouter();
    const queryClient = useQueryClient();

    return () => {
        removeToken();
        queryClient.removeQueries({ queryKey: ['me'] });
        router.push('/login');
        toast.success('Вы вышли из аккаунта');
    };
};
