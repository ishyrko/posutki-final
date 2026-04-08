import { NextRequest, NextResponse } from 'next/server';
import { AUTH_TOKEN_KEY } from '@/lib/auth-constants';

export function middleware(request: NextRequest) {
    const token = request.cookies.get(AUTH_TOKEN_KEY)?.value;

    if (token) {
        return NextResponse.redirect(new URL('/kabinet', request.url));
    }

    return NextResponse.next();
}

export const config = {
    matcher: ['/login', '/register', '/reset-password', '/reset-password/confirm'],
};
