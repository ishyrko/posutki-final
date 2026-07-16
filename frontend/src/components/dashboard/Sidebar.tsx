'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';
import { Home, Heart, MessageSquare, User, LogOut, ChevronRight, CreditCard } from 'lucide-react';
import { useUser, useLogout } from '@/features/auth/hooks';
import { useUnreadCount } from '@/features/messages/hooks';
import { useUnreadBookingInquiryCount } from '@/features/properties/booking-inquiry';
import { usePendingPlacementPaymentCount } from '@/features/placement/hooks';
import { UserAvatar } from '@/components/UserAvatar';
import { formatUserDisplayName } from '@/features/profile/displayName';

const navigation = [
    { name: 'Профиль', href: '/kabinet/profil', icon: User },
    { name: 'Мои объявления', mobileName: 'Объявления', href: '/kabinet/moi-obyavleniya/aktivnye', activePrefix: '/kabinet/moi-obyavleniya', icon: Home },
    { name: 'Избранное', href: '/kabinet/izbrannoe', icon: Heart },
    { name: 'Оплаты', href: '/kabinet/oplata', activePrefix: '/kabinet/oplata', icon: CreditCard, badgeKey: 'pendingPayments' as const },
    { name: 'Сообщения', href: '/kabinet/soobshcheniya', icon: MessageSquare, badgeKey: 'unread' as const },
];

function getNavBadgeCount(
    badgeKey: 'unread' | 'pendingPayments' | undefined,
    totalUnreadCount: number,
    pendingPaymentCount: number,
): number {
    if (badgeKey === 'unread') {
        return totalUnreadCount;
    }
    if (badgeKey === 'pendingPayments') {
        return pendingPaymentCount;
    }
    return 0;
}

function normalizePath(path: string) {
    return path.replace(/\/+$/, '') || '/';
}

function isNavItemActive(pathname: string, href: string, activePrefix?: string) {
    const current = normalizePath(pathname);
    const target = normalizePath(href);
    const prefix = activePrefix ? normalizePath(activePrefix) : null;

    if (prefix) {
        return current === prefix || current.startsWith(`${prefix}/`);
    }

    return current === target || current.startsWith(`${target}/`);
}

export function Sidebar() {
    const pathname = usePathname();
    const { data: user } = useUser();
    const logout = useLogout();
    const { data: unreadCount } = useUnreadCount();
    const { data: unreadBookingInquiryCount } = useUnreadBookingInquiryCount();
    const { data: pendingPaymentCount } = usePendingPlacementPaymentCount();
    const totalUnreadCount = (unreadCount ?? 0) + (unreadBookingInquiryCount ?? 0);

    return (
        <>
            {/* Desktop sidebar */}
            <aside className="hidden lg:flex flex-col w-64 shrink-0">
                <nav className="sticky top-20 space-y-1 rounded-xl p-4 bg-card shadow-card">
                    <div className="flex items-center gap-3 p-3 mb-3 rounded-xl bg-muted">
                        <UserAvatar user={user} className="h-10 w-10 text-sm border-0 shrink-0" />
                        <div className="min-w-0">
                            <div className="font-display font-semibold text-foreground text-sm truncate">
                                {formatUserDisplayName(user) || 'Профиль'}
                            </div>
                            <div className="text-xs text-muted-foreground truncate">{user?.email ?? '—'}</div>
                        </div>
                    </div>

                    {navigation.map((item) => {
                        const isActive = isNavItemActive(pathname, item.href, item.activePrefix);
                        const badgeCount = getNavBadgeCount(
                            item.badgeKey,
                            totalUnreadCount,
                            pendingPaymentCount ?? 0,
                        );
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-primary/10 text-primary hover:bg-primary/10 hover:text-primary'
                                        : 'text-muted-foreground hover:text-foreground hover:bg-muted',
                                )}
                            >
                                <item.icon className="h-4 w-4 shrink-0" />
                                <span className="truncate">{item.name}</span>
                                {isActive &&
                                    (item.badgeKey && badgeCount > 0 ? (
                                        <span className="ml-auto min-w-[1.25rem] h-5 px-1 rounded-full bg-primary text-primary-foreground text-xs flex items-center justify-center shrink-0">
                                            {badgeCount > 99 ? '99+' : badgeCount}
                                        </span>
                                    ) : (
                                        <ChevronRight className="h-4 w-4 ml-auto shrink-0" />
                                    ))}
                                {!isActive && item.badgeKey && badgeCount > 0 && (
                                    <span className="ml-auto min-w-[1.25rem] h-5 px-1 rounded-full bg-primary text-primary-foreground text-xs flex items-center justify-center shrink-0">
                                        {badgeCount > 99 ? '99+' : badgeCount}
                                    </span>
                                )}
                            </Link>
                        );
                    })}

                    <div className="border-t border-border my-3" />

                    <button
                        type="button"
                        onClick={logout}
                        className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors w-full"
                    >
                        <LogOut className="h-4 w-4 shrink-0" />
                        <span>Выйти</span>
                    </button>
                </nav>
            </aside>

            {/* Mobile bottom tab bar */}
            <div className="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-card border-t border-border flex pb-[env(safe-area-inset-bottom,0px)]">
                {navigation.map((item) => {
                    const isActive = isNavItemActive(pathname, item.href, item.activePrefix);
                    const badgeCount = getNavBadgeCount(
                        item.badgeKey,
                        totalUnreadCount,
                        pendingPaymentCount ?? 0,
                    );
                    return (
                        <Link
                            key={item.name}
                            href={item.href}
                            className={cn(
                                'relative flex-1 flex flex-col items-center gap-1 py-3 text-xs transition-colors',
                                isActive ? 'text-primary' : 'text-muted-foreground',
                            )}
                        >
                            {isActive && (
                                <span className="absolute top-0 left-3 right-3 h-0.5 rounded-full bg-primary" />
                            )}
                            <item.icon className="w-5 h-5" />
                            {item.mobileName ?? item.name}
                            {item.badgeKey && badgeCount > 0 && (
                                <span className="absolute top-1.5 right-[calc(50%-1.25rem)] min-w-[1rem] h-4 px-0.5 rounded-full bg-primary text-primary-foreground text-[10px] flex items-center justify-center leading-none">
                                    {badgeCount > 99 ? '99+' : badgeCount}
                                </span>
                            )}
                        </Link>
                    );
                })}
            </div>
        </>
    );
}
