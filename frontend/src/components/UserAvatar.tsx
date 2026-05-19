import { cn } from '@/lib/utils';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { resolveUserAvatarUrl } from '@/features/profile/resolveAvatarUrl';
import { getUserInitials } from '@/features/profile/displayName';

export type UserAvatarUser = {
    firstName?: string | null;
    lastName?: string | null;
    avatar?: string | null;
};

type UserAvatarProps = {
    user?: UserAvatarUser | null;
    /** Корневой элемент Avatar: размер, скругление, рамка */
    className?: string;
    /** Стили блока с инициалами (когда фото нет или ошибка загрузки) */
    fallbackClassName?: string;
};

export function UserAvatar({ user, className, fallbackClassName }: UserAvatarProps) {
    const initials = user ? getUserInitials(user) : '';
    const avatarSrc = user ? resolveUserAvatarUrl(user.avatar) : null;

    return (
        <Avatar className={cn('shrink-0 border border-border bg-primary/10 text-primary font-semibold', className)}>
            {avatarSrc ? <AvatarImage src={avatarSrc} alt="" className="object-cover" /> : null}
            <AvatarFallback
                delayMs={0}
                className={cn('bg-primary/10 text-primary font-semibold', fallbackClassName)}
            >
                {initials || '?'}
            </AvatarFallback>
        </Avatar>
    );
}
