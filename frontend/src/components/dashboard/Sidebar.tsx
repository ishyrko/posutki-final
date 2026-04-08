'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { cn } from '@/lib/utils';
import { Home, Heart, MessageSquare, User, LogOut, Phone } from 'lucide-react';
import { useUser, useLogout } from '@/features/auth/hooks';
import { useUnreadCount } from '@/features/messages/hooks';
import { UserAvatar } from '@/components/UserAvatar';

const navigation = [
    { name: 'Мои объявления', mobileName: 'Объявления', href: '/kabinet/moi-obyavleniya/aktivnye', activePrefix: '/kabinet/moi-obyavleniya', icon: Home },
    { name: 'Избранное', href: '/kabinet/izbrannoe', icon: Heart },
    { name: 'Сообщения', href: '/kabinet/soobshcheniya', icon: MessageSquare, badgeKey: 'unread' as const },
    { name: 'Телефоны', href: '/kabinet/telefony', icon: Phone },
    { name: 'Профиль', href: '/kabinet/profil', icon: User },
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
    const { data: user } = useUser();
    const logout = useLogout();
    const { data: unreadCount } = useUnreadCount();

    return (
        <>
            {/* Desktop sidebar */}
            <aside className="hidden md:flex flex-col w-64 min-h-[calc(100vh-4rem)] border-r border-border bg-card p-4">
                <div className="flex items-center gap-3 mb-6 p-3 rounded-xl bg-muted/50">
                    <UserAvatar user={user} className="h-10 w-10 text-sm border-0" />
                    <div className="min-w-0">
                        <p className="text-sm font-semibold text-foreground truncate">
                            {user?.firstName} {user?.lastName}
                        </p>
                        <p className="text-xs text-muted-foreground truncate">{user?.email}</p>
                    </div>
                </div>

                <nav className="flex flex-col gap-1 flex-1">
                    {navigation.map((item) => {
                        const isActive = isNavItemActive(pathname, item.href, item.activePrefix);
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-primary/10 text-primary'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                )}
                            >
                                <item.icon className="w-4 h-4" />
                                {item.name}
                                {item.badgeKey === 'unread' && !!unreadCount && unreadCount > 0 && (
                                    <span className="ml-auto w-5 h-5 rounded-full bg-primary text-primary-foreground text-xs flex items-center justify-center">
                                        {unreadCount}
                                    </span>
                                )}
                            </Link>
                        );
                    })}
                </nav>

                <button
                    onClick={logout}
                    className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors mt-2"
                >
                    <LogOut className="w-4 h-4" />
                    Выйти
                </button>
            </aside>

            {/* Mobile bottom tabs */}
            <div className="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-card border-t border-border flex pb-[env(safe-area-inset-bottom,0px)]">
                {navigation.map((item) => {
                    const isActive = isNavItemActive(pathname, item.href, item.activePrefix);
                    return (
                        <Link
                            key={item.name}
                            href={item.href}
                            className={cn(
                                'relative flex-1 flex flex-col items-center gap-1 py-3 text-xs transition-colors',
                                isActive ? 'text-orange-500' : 'text-muted-foreground'
                            )}
                        >
                            {isActive && (
                                <span className="absolute top-0 left-3 right-3 h-0.5 rounded-full bg-orange-500" />
                            )}
                            <item.icon className="w-5 h-5" />
                            {item.mobileName ?? item.name}
                        </Link>
                    );
                })}
            </div>
        </>
    );
}
