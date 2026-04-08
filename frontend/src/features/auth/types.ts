export interface User {
    id: number;
    email: string | null;
    pendingEmail?: string | null;
    firstName: string;
    lastName: string;
    /** URL аватара (например с Google или загрузка) */
    avatar?: string | null;
    phone?: string;
    isPhoneVerified: boolean;
    isVerified?: boolean;
    roles: string[];
}

export interface LoginResponse {
    token: string;
}

export interface RegisterResponse {
    success: true;
    data: {
        userId: number;
    };
}

export interface AuthResponse {
    user: User;
}
