'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';
import { Home, Heart, MessageSquare, User, LogOut, Phone, Menu, X, ChevronRight } from 'lucide-react';
import { useUser, useLogout } from '@/features/auth/hooks';
import { useUnreadCount } from '@/features/messages/hooks';
import { UserAvatar } from '@/components/UserAvatar';

const navigation = [
    { name: 'Профиль', href: '/kabinet/profil', icon: User },
    { name: 'Мои объявления', mobileName: 'Объявления', href: '/kabinet/moi-obyavleniya/aktivnye', activePrefix: '/kabinet/moi-obyavleniya', icon: Home },
    { name: 'Избранное', href: '/kabinet/izbrannoe', icon: Heart },
    { name: 'Сообщения', href: '/kabinet/soobshcheniya', icon: MessageSquare, badgeKey: 'unread' as const },
    { name: 'Телефоны', href: '/kabinet/telefony', icon: Phone },
];

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
    const [mobileOpen, setMobileOpen] = useState(false);
    const { data: user } = useUser();
    const logout = useLogout();
    const { data: unreadCount } = useUnreadCount();

    useEffect(() => {
        if (mobileOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [mobileOpen]);

    useEffect(() => {
        setMobileOpen(false);
    }, [pathname]);

    return (
        <>
            <button
                type="button"
                onClick={() => setMobileOpen(!mobileOpen)}
                className="lg:hidden fixed bottom-6 right-6 z-50 p-3.5 rounded-full bg-primary text-primary-foreground shadow-elevated"
                aria-label={mobileOpen ? 'Закрыть меню' : 'Открыть меню кабинета'}
            >
                {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </button>

            <aside
                className={cn(
                    'w-full lg:w-64 shrink-0',
                    mobileOpen
                        ? 'fixed inset-0 z-40 bg-background/80 backdrop-blur-sm lg:relative lg:inset-auto lg:bg-transparent lg:backdrop-blur-none'
                        : 'hidden lg:block',
                )}
                onClick={(e) => {
                    if (e.target === e.currentTarget) setMobileOpen(false);
                }}
            >
                <nav
                    className={cn(
                        'space-y-1 rounded-xl p-4',
                        mobileOpen
                            ? 'fixed left-0 top-16 bottom-0 z-50 w-72 max-w-[min(18rem,100vw-1.5rem)] overflow-y-auto bg-card shadow-elevated animate-fade-in'
                            : 'sticky top-20',
                        'lg:relative lg:sticky lg:top-20 lg:z-auto lg:max-w-none lg:w-full lg:bg-card lg:shadow-card',
                    )}
                >
                    <div className="flex items-center gap-3 p-3 mb-3 rounded-xl bg-muted">
                        <UserAvatar user={user} className="h-10 w-10 text-sm border-0 shrink-0" />
                        <div className="min-w-0">
                            <div className="font-display font-semibold text-foreground text-sm truncate">
                                {user?.firstName || user?.lastName
                                    ? [user?.firstName, user?.lastName].filter(Boolean).join(' ')
                                    : 'Профиль'}
                            </div>
                            <div className="text-xs text-muted-foreground truncate">{user?.email ?? '—'}</div>
                        </div>
                    </div>

                    {navigation.map((item) => {
                        const isActive = isNavItemActive(pathname, item.href, item.activePrefix);
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                onClick={() => setMobileOpen(false)}
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
                                    (item.badgeKey === 'unread' && !!unreadCount && unreadCount > 0 ? (
                                        <span className="ml-auto min-w-[1.25rem] h-5 px-1 rounded-full bg-primary text-primary-foreground text-xs flex items-center justify-center shrink-0">
                                            {unreadCount > 99 ? '99+' : unreadCount}
                                        </span>
                                    ) : (
                                        <ChevronRight className="h-4 w-4 ml-auto shrink-0" />
                                    ))}
                                {!isActive && item.badgeKey === 'unread' && !!unreadCount && unreadCount > 0 && (
                                    <span className="ml-auto min-w-[1.25rem] h-5 px-1 rounded-full bg-primary text-primary-foreground text-xs flex items-center justify-center shrink-0">
                                        {unreadCount > 99 ? '99+' : unreadCount}
                                    </span>
                                )}
                            </Link>
                        );
                    })}

                    <div className="border-t border-border my-3" />

                    <button
                        type="button"
                        onClick={() => {
                            setMobileOpen(false);
                            logout();
                        }}
                        className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors w-full"
                    >
                        <LogOut className="h-4 w-4 shrink-0" />
                        <span>Выйти</span>
                    </button>
                </nav>
            </aside>
        </>
    );
}
