import { NextRequest, NextResponse } from 'next/server';
import { AUTH_TOKEN_KEY, resolveAuthRedirectPath } from '@/lib/auth-constants';

export function middleware(request: NextRequest) {
    const token = request.cookies.get(AUTH_TOKEN_KEY)?.value;
    const { pathname, search } = request.nextUrl;

    const isAuthPage =
        pathname === '/login' ||
        pathname === '/register' ||
        pathname === '/reset-password' ||
        pathname === '/reset-password/confirm';

    // Only authenticated users may access listing submission.
    const isListingSubmissionPage = pathname === '/razmestit' || pathname.startsWith('/razmestit/');

    if (isListingSubmissionPage && !token) {
        const next = `${pathname}${search}`;
        const url = new URL(`/login?next=${encodeURIComponent(next)}`, request.url);
        return NextResponse.redirect(url);
    }

    if (isAuthPage && token) {
        const next = resolveAuthRedirectPath(request.nextUrl.searchParams.get('next'));
        const destination = next ?? '/kabinet/';
        return NextResponse.redirect(new URL(destination, request.url));
    }

    return NextResponse.next();
}

export const config = {
    matcher: ['/login', '/register', '/reset-password', '/reset-password/confirm', '/razmestit/:path*'],
};
