import { NextRequest, NextResponse } from 'next/server';
import { AUTH_TOKEN_KEY, resolveAuthRedirectPath } from '@/lib/auth-constants';

function shouldForceHttps(): boolean {
    return process.env.NODE_ENV === 'production' && process.env.FORCE_HTTPS !== '0';
}

function isHttpsRequest(request: NextRequest): boolean {
    const forwarded = request.headers.get('x-forwarded-proto');
    if (forwarded) {
        return forwarded.split(',')[0].trim().toLowerCase() === 'https';
    }
    return request.nextUrl.protocol === 'https:';
}

function redirectToHttps(request: NextRequest): NextResponse | null {
    if (!shouldForceHttps() || isHttpsRequest(request)) {
        return null;
    }
    const host = request.headers.get('host') ?? request.nextUrl.host;
    const target = new URL(`${request.nextUrl.pathname}${request.nextUrl.search}`, `https://${host}`);
    return NextResponse.redirect(target, 301);
}

export function middleware(request: NextRequest) {
    const httpsRedirect = redirectToHttps(request);
    if (httpsRedirect) {
        return httpsRedirect;
    }

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
    matcher: [
        /*
         * All pages except static assets (HTTPS redirect + auth guards on listed routes).
         */
        '/((?!_next/static|_next/image|favicon.ico|.*\\.(?:svg|png|jpg|jpeg|gif|webp|ico)$).*)',
    ],
};
