import { Suspense } from 'react';
import { LoginForm } from '@/features/auth/components/LoginForm';

export default function LoginPage() {
    return (
        <Suspense fallback={<div className="min-h-screen bg-dark-bg" />}>
            <LoginForm />
        </Suspense>
    );
}
