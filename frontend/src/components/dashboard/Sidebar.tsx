'use client';

import Link from 'next/link';
import { useMemo } from 'react';
import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';
import { Home, Heart, MessageSquare, User, LogOut, ChevronRight, CreditCard, Star } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useUser, useLogout } from '@/features/auth/hooks';
import { useUnreadCount } from '@/features/messages/hooks';
import { useUnreadBookingInquiryCount } from '@/features/properties/booking-inquiry';
import { useMyProperties } from '@/features/properties/hooks';
import { useOwnerFeaturesContext } from '@/features/properties/OwnerFeaturesProvider';
import { usePendingPlacementPaymentCount } from '@/features/placement/hooks';
import { UserAvatar } from '@/components/UserAvatar';
import { formatUserDisplayName } from '@/features/profile/displayName';

type NavBadgeKey = 'unread' | 'pendingPayments' | 'reviews';

type NavItem = {
    name: string;
    mobileName?: string;
    href: string;
    activePrefix?: string;
    icon: LucideIcon;
    badgeKey?: NavBadgeKey;
    ownerOnly?: boolean;
    hideOnMobileWhenOwner?: boolean;
};

const navigation: NavItem[] = [
    { name: 'Профиль', href: '/kabinet/profil', icon: User },
    {
        name: 'Мои объявления',
        mobileName: 'Объявления',
        href: '/kabinet/moi-obyavleniya/aktivnye',
        activePrefix: '/kabinet/moi-obyavleniya',
        icon: Home,
    },
    { name: 'Избранное', href: '/izbrannoe', icon: Heart, hideOnMobileWhenOwner: true },
    {
        name: 'Оплаты',
        href: '/kabinet/oplata',
        activePrefix: '/kabinet/oplata',
        icon: CreditCard,
        badgeKey: 'pendingPayments',
        ownerOnly: true,
    },
    { name: 'Сообщения', href: '/kabinet/soobshcheniya', icon: MessageSquare, badgeKey: 'unread' },
    {
        name: 'Отзывы',
        href: '/kabinet/otzyvy/',
        activePrefix: '/kabinet/otzyvy',
        icon: Star,
        badgeKey: 'reviews',
    },
];

function getNavBadgeCount(
    badgeKey: NavBadgeKey | undefined,
    totalUnreadCount: number,
    pendingPaymentCount: number,
    unviewedReviewsCount: number,
): number {
    if (badgeKey === 'unread') {
        return totalUnreadCount;
    }
    if (badgeKey === 'pendingPayments') {
        return pendingPaymentCount;
    }
    if (badgeKey === 'reviews') {
        return unviewedReviewsCount;
    }
    return 0;
}

function filterNavigation(items: NavItem[], hasMyProperties: boolean, mobile: boolean): NavItem[] {
    return items.filter((item) => {
        if (item.ownerOnly && !hasMyProperties) {
            return false;
        }
        if (mobile && item.hideOnMobileWhenOwner && hasMyProperties) {
            return false;
        }
        return true;
    });
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

function NavLink({
    item,
    pathname,
    badgeCount,
    mobile,
}: {
    item: NavItem;
    pathname: string;
    badgeCount: number;
    mobile?: boolean;
}) {
    const isActive = isNavItemActive(pathname, item.href, item.activePrefix);

    if (mobile) {
        return (
            <Link
                href={item.href}
                className={cn(
                    'relative flex-1 flex flex-col items-center gap-1 py-3 text-xs transition-colors',
                    isActive ? 'text-primary' : 'text-muted-foreground',
                )}
            >
                {isActive && <span className="absolute top-0 left-3 right-3 h-0.5 rounded-full bg-primary" />}
                <item.icon className="w-5 h-5" />
                {item.mobileName ?? item.name}
                {item.badgeKey && badgeCount > 0 && (
                    <span className="absolute top-1.5 right-[calc(50%-1.25rem)] min-w-[1rem] h-4 px-0.5 rounded-full bg-primary text-primary-foreground text-[10px] flex items-center justify-center leading-none">
                        {badgeCount > 99 ? '99+' : badgeCount}
                    </span>
                )}
            </Link>
        );
    }

    return (
        <Link
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
}

export function Sidebar() {
    const pathname = usePathname();
    const { data: user } = useUser();
    const logout = useLogout();
    const ownerFeatures = useOwnerFeaturesContext();
    const myPropertiesQuery = useMyProperties(1, 50);
    const hasMyProperties = myPropertiesQuery.isSuccess
        ? (myPropertiesQuery.data?.data.length ?? 0) > 0
        : (ownerFeatures?.initialHasMyProperties ?? false);
    const unviewedReviewsCount = useMemo(
        () =>
            (myPropertiesQuery.data?.data ?? []).reduce(
                (sum, property) => sum + (property.unviewedReviewsCount ?? 0),
                0,
            ),
        [myPropertiesQuery.data?.data],
    );
    const { data: unreadCount } = useUnreadCount();
    const { data: unreadBookingInquiryCount } = useUnreadBookingInquiryCount();
    const { data: pendingPaymentCount } = usePendingPlacementPaymentCount();
    const totalUnreadCount = (unreadCount ?? 0) + (hasMyProperties ? (unreadBookingInquiryCount ?? 0) : 0);
    const navigationWithReviewsHref = useMemo(
        () =>
            navigation.map((item) =>
                item.name === 'Отзывы'
                    ? { ...item, href: hasMyProperties ? '/kabinet/otzyvy/' : '/kabinet/otzyvy/moi/' }
                    : item,
            ),
        [hasMyProperties],
    );
    const desktopNavigation = useMemo(
        () => filterNavigation(navigationWithReviewsHref, hasMyProperties, false),
        [navigationWithReviewsHref, hasMyProperties],
    );
    const mobileNavigation = useMemo(
        () => filterNavigation(navigationWithReviewsHref, hasMyProperties, true),
        [navigationWithReviewsHref, hasMyProperties],
    );

    return (
        <>
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

                    {desktopNavigation.map((item) => (
                        <NavLink
                            key={item.name}
                            item={item}
                            pathname={pathname}
                            badgeCount={getNavBadgeCount(
                                item.badgeKey,
                                totalUnreadCount,
                                pendingPaymentCount ?? 0,
                                unviewedReviewsCount,
                            )}
                        />
                    ))}

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

            <div className="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-card border-t border-border flex pb-[env(safe-area-inset-bottom,0px)]">
                {mobileNavigation.map((item) => (
                    <NavLink
                        key={item.name}
                        item={item}
                        pathname={pathname}
                        mobile
                        badgeCount={getNavBadgeCount(
                            item.badgeKey,
                            totalUnreadCount,
                            pendingPaymentCount ?? 0,
                            unviewedReviewsCount,
                        )}
                    />
                ))}
            </div>
        </>
    );
}
