import { ConfirmResetPasswordForm } from '@/features/auth/components/ConfirmResetPasswordForm';

export const dynamic = 'force-dynamic';

type ConfirmResetPasswordPageProps = {
    searchParams: Promise<{
        email?: string;
        token?: string;
    }>;
};

export default async function ConfirmResetPasswordPage({
    searchParams,
}: ConfirmResetPasswordPageProps) {
    const resolvedSearchParams = await searchParams;
    const email = resolvedSearchParams.email ?? '';
    const token = resolvedSearchParams.token ?? '';

    return <ConfirmResetPasswordForm email={email} token={token} />;
}
