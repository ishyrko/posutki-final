'use client';

import { useRef, useState } from 'react';
import { Camera, Loader2 } from 'lucide-react';
import { toast } from 'sonner';
import type { User } from '@/features/auth/types';
import { UserAvatar } from '@/components/UserAvatar';
import { uploadAvatar } from '../api';
import { useUpdateProfile } from '../hooks';
import { FileTooLargeError } from '@/features/create-listing/api';

const ACCEPT = 'image/jpeg,image/png,image/webp';
const MAX_BYTES = 5 * 1024 * 1024;

type ProfileAvatarEditorProps = {
    user?: User | null;
    className?: string;
    avatarClassName?: string;
    fallbackClassName?: string;
};

export function ProfileAvatarEditor({
    user,
    className,
    avatarClassName = 'h-14 w-14 border border-border text-sm font-bold',
    fallbackClassName = 'text-sm font-bold',
}: ProfileAvatarEditorProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const { mutate: updateProfile, isPending: isSavingProfile } = useUpdateProfile();
    const [isUploading, setIsUploading] = useState(false);
    const busy = isUploading || isSavingProfile;

    const handlePickFile = () => {
        if (!user || busy) return;
        inputRef.current?.click();
    };

    const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        event.target.value = '';
        if (!file || !user) return;

        if (!ACCEPT.split(',').includes(file.type)) {
            toast.error('Допустимы только JPEG, PNG или WebP');
            return;
        }
        if (file.size > MAX_BYTES) {
            toast.error('Файл слишком большой (максимум 5 МБ)');
            return;
        }

        setIsUploading(true);
        try {
            const url = await uploadAvatar(file);
            updateProfile({ avatar: url });
        } catch (error) {
            if (error instanceof FileTooLargeError) {
                toast.error('Файл слишком большой (максимум 5 МБ)');
            } else {
                toast.error('Не удалось загрузить фото');
            }
        } finally {
            setIsUploading(false);
        }
    };

    return (
        <div className={className ?? 'relative shrink-0'}>
            <UserAvatar
                user={user}
                className={avatarClassName}
                fallbackClassName={fallbackClassName}
            />
            <button
                type="button"
                disabled={!user || busy}
                onClick={handlePickFile}
                className="absolute -bottom-0.5 -right-0.5 w-6 h-6 rounded-full bg-primary text-primary-foreground flex items-center justify-center shadow-primary disabled:opacity-60"
                aria-label="Изменить фото профиля"
                title="Изменить фото"
            >
                {busy ? (
                    <Loader2 className="w-3 h-3 animate-spin" />
                ) : (
                    <Camera className="w-3 h-3" />
                )}
            </button>
            <input
                ref={inputRef}
                type="file"
                accept={ACCEPT}
                className="sr-only"
                tabIndex={-1}
                onChange={(event) => void handleFileChange(event)}
            />
        </div>
    );
}
