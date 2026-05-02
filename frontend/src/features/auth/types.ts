/** Реквизиты физлица для посуточных объявлений (отдельно от имени в аккаунте). */
export interface UserIndividualProfile {
    lastName: string;
    firstName: string;
    middleName?: string | null;
    unp: string;
}

/** Реквизиты организации для посуточных объявлений. */
export interface UserBusinessProfile {
    organizationName: string;
    contactName: string;
    unp: string;
}

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
    individualProfile?: UserIndividualProfile | null;
    businessProfile?: UserBusinessProfile | null;
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
