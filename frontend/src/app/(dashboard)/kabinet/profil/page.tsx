'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useUser } from '@/features/auth/hooks';
import { useUpdateProfile, useChangePassword, useUpdateEmail } from '@/features/profile/hooks';
import { updateProfileSchema, UpdateProfileData, changePasswordSchema, ChangePasswordData } from '@/features/profile/api';
import { motion } from 'framer-motion';
import { Camera, Phone, Mail, Lock, Bell, CheckCircle2, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { useEffect, useState } from 'react';
import { PhoneVerifyDialog } from '@/features/phones/components/PhoneVerifyDialog';
import { UserAvatar } from '@/components/UserAvatar';

export default function ProfilePage() {
    const { data: user } = useUser();
    const { mutate: updateProfile, isPending: isUpdating } = useUpdateProfile();
    const { mutate: changePassword, isPending: isChangingPassword } = useChangePassword();
    const { mutate: saveEmail, isPending: isSavingEmail } = useUpdateEmail();
    const [verifyDialogOpen, setVerifyDialogOpen] = useState(false);
    const [emailDraft, setEmailDraft] = useState('');
    const [emailEditing, setEmailEditing] = useState(false);

    const profileForm = useForm<UpdateProfileData>({
        resolver: zodResolver(updateProfileSchema),
        defaultValues: {
            firstName: '',
            lastName: '',
            phone: '',
            avatar: '',
        },
    });

    const passwordForm = useForm<ChangePasswordData>({
        resolver: zodResolver(changePasswordSchema),
        defaultValues: {
            currentPassword: '',
            newPassword: '',
            confirmPassword: '',
        },
    });

    useEffect(() => {
        if (user) {
            profileForm.reset({
                firstName: user.firstName || '',
                lastName: user.lastName || '',
                phone: user.phone || '',
                avatar: '',
            });
            setEmailDraft(user.email || '');
            setEmailEditing(!user.email);
        }
    }, [user, profileForm]);

    const handleSaveEmail = () => {
        const trimmed = emailDraft.trim();
        if (!trimmed) {
            return;
        }
        saveEmail(trimmed, {
            onSuccess: () => {
                setEmailEditing(false);
            },
        });
    };

    const handleCancelEmailEdit = () => {
        setEmailDraft(user?.email || '');
        setEmailEditing(false);
    };

    const onProfileSubmit = (data: UpdateProfileData) => {
        updateProfile(data);
    };

    const onPasswordSubmit = (data: ChangePasswordData) => {
        changePassword(data, {
            onSuccess: () => passwordForm.reset(),
        });
    };

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
            className="max-w-2xl"
        >
            <h1 className="text-2xl font-display font-bold text-foreground mb-6">
                Профиль и настройки
            </h1>

            {/* Avatar & personal info */}
            <div className="bg-card rounded-xl border border-border p-6 shadow-card mb-6">
                <div className="flex items-center gap-4 mb-6">
                    <div className="relative">
                        <UserAvatar
                            user={user}
                            className="h-20 w-20 border border-border text-2xl font-bold"
                            fallbackClassName="text-2xl font-bold"
                        />
                        <button className="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-primary text-primary-foreground flex items-center justify-center shadow-primary">
                            <Camera className="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <div>
                        <h3 className="font-semibold text-foreground text-lg">
                            {user?.firstName} {user?.lastName}
                        </h3>
                        <p className="text-sm text-muted-foreground">{user?.email}</p>
                    </div>
                </div>

                <form onSubmit={profileForm.handleSubmit(onProfileSubmit)}>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">Имя</label>
                            <Input {...profileForm.register('firstName')} />
                            {profileForm.formState.errors.firstName && (
                                <p className="text-destructive text-xs mt-1">{profileForm.formState.errors.firstName.message}</p>
                            )}
                        </div>
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">Фамилия</label>
                            <Input {...profileForm.register('lastName')} />
                            {profileForm.formState.errors.lastName && (
                                <p className="text-destructive text-xs mt-1">{profileForm.formState.errors.lastName.message}</p>
                            )}
                        </div>
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block flex items-center gap-1.5">
                                <Phone className="w-3.5 h-3.5" />Телефон
                                {user?.phone && (
                                    user.isPhoneVerified ? (
                                        <Badge variant="default" className="ml-1 gap-1 text-[10px] px-1.5 py-0">
                                            <CheckCircle2 className="w-2.5 h-2.5" />Подтверждён
                                        </Badge>
                                    ) : (
                                        <Badge variant="secondary" className="ml-1 gap-1 text-[10px] px-1.5 py-0">
                                            <AlertCircle className="w-2.5 h-2.5" />Не подтверждён
                                        </Badge>
                                    )
                                )}
                            </label>
                            <div className="flex gap-2">
                                <Input {...profileForm.register('phone')} placeholder="Например: +375 29 123-45-67" />
                                {user?.phone && !user.isPhoneVerified && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        className="shrink-0"
                                        onClick={() => setVerifyDialogOpen(true)}
                                    >
                                        Подтвердить
                                    </Button>
                                )}
                            </div>
                        </div>
                        <div className="sm:col-span-2">
                            <label className="text-sm text-muted-foreground mb-1.5 block flex flex-wrap items-center gap-1.5">
                                <Mail className="w-3.5 h-3.5" />Email
                                {user?.email && user.isVerified && (
                                    <Badge variant="default" className="ml-1 gap-1 text-[10px] px-1.5 py-0">
                                        <CheckCircle2 className="w-2.5 h-2.5" />Подтверждён
                                    </Badge>
                                )}
                                {user?.pendingEmail && (
                                    <Badge variant="secondary" className="ml-1 gap-1 text-[10px] px-1.5 py-0">
                                        <AlertCircle className="w-2.5 h-2.5" />Ожидает подтверждения
                                    </Badge>
                                )}
                                {!user?.email && (
                                    <Badge variant="outline" className="ml-1 text-[10px] px-1.5 py-0">
                                        Не указан
                                    </Badge>
                                )}
                            </label>
                            {user?.pendingEmail && (
                                <p className="text-xs text-muted-foreground mb-2">
                                    Письмо отправлено на <span className="text-foreground font-medium">{user.pendingEmail}</span>.
                                    Перейдите по ссылке в письме.
                                </p>
                            )}
                            <div className="flex flex-col sm:flex-row gap-2">
                                <Input
                                    type="email"
                                    autoComplete="email"
                                    placeholder="you@example.com"
                                    value={emailDraft}
                                    onChange={(e) => setEmailDraft(e.target.value)}
                                    disabled={!!user?.email && !emailEditing}
                                    className={!!user?.email && !emailEditing ? 'opacity-70' : ''}
                                />
                                <div className="flex gap-2 shrink-0">
                                    {user?.email && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => (emailEditing ? handleCancelEmailEdit() : setEmailEditing(true))}
                                        >
                                            {emailEditing ? 'Отмена' : 'Изменить'}
                                        </Button>
                                    )}
                                    <Button
                                        type="button"
                                        size="sm"
                                        className="bg-gradient-primary text-primary-foreground border-0"
                                        onClick={handleSaveEmail}
                                        disabled={
                                            isSavingEmail ||
                                            !emailDraft.trim() ||
                                            (!!user?.email && !emailEditing)
                                        }
                                    >
                                        {isSavingEmail ? 'Отправка...' : 'Сохранить email'}
                                    </Button>
                                </div>
                            </div>
                            <p className="text-xs text-muted-foreground mt-1.5">
                                После смены или добавления адреса проверьте почту и подтвердите email по ссылке.
                            </p>
                        </div>
                    </div>

                    <div className="flex justify-end mt-4">
                        <Button
                            type="submit"
                            disabled={isUpdating}
                            className="bg-gradient-primary text-primary-foreground border-0"
                        >
                            {isUpdating ? 'Сохранение...' : 'Сохранить'}
                        </Button>
                    </div>
                </form>
            </div>

            {/* Password */}
            <div className="bg-card rounded-xl border border-border p-6 shadow-card mb-6">
                <h3 className="font-semibold text-foreground mb-4 flex items-center gap-2">
                    <Lock className="w-4 h-4" />Смена пароля
                </h3>
                <form onSubmit={passwordForm.handleSubmit(onPasswordSubmit)}>
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">Текущий пароль</label>
                            <Input type="password" placeholder="••••••••" {...passwordForm.register('currentPassword')} />
                            {passwordForm.formState.errors.currentPassword && (
                                <p className="text-destructive text-xs mt-1">{passwordForm.formState.errors.currentPassword.message}</p>
                            )}
                        </div>
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">Новый пароль</label>
                            <Input type="password" placeholder="••••••••" {...passwordForm.register('newPassword')} />
                            {passwordForm.formState.errors.newPassword && (
                                <p className="text-destructive text-xs mt-1">{passwordForm.formState.errors.newPassword.message}</p>
                            )}
                        </div>
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">Подтверждение</label>
                            <Input type="password" placeholder="••••••••" {...passwordForm.register('confirmPassword')} />
                            {passwordForm.formState.errors.confirmPassword && (
                                <p className="text-destructive text-xs mt-1">{passwordForm.formState.errors.confirmPassword.message}</p>
                            )}
                        </div>
                    </div>
                    <div className="flex justify-end mt-4">
                        <Button type="submit" variant="outline" disabled={isChangingPassword}>
                            {isChangingPassword ? 'Обновление...' : 'Обновить пароль'}
                        </Button>
                    </div>
                </form>
            </div>

            {/* Notifications */}
            <div className="bg-card rounded-xl border border-border p-6 shadow-card">
                <h3 className="font-semibold text-foreground mb-4 flex items-center gap-2">
                    <Bell className="w-4 h-4" />Уведомления
                </h3>
                <div className="space-y-4">
                    {[
                        { label: 'Новые сообщения', desc: 'Уведомления о входящих сообщениях', defaultOn: true },
                        { label: 'Отклики на объявления', desc: 'Когда кто-то откликается на ваше объявление', defaultOn: true },
                        { label: 'Изменения цен в избранном', desc: 'Уведомления об изменении цен', defaultOn: false },
                        { label: 'Новости и акции', desc: 'Рассылка о новых функциях сервиса', defaultOn: false },
                    ].map((item) => (
                        <div key={item.label} className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-foreground">{item.label}</p>
                                <p className="text-xs text-muted-foreground">{item.desc}</p>
                            </div>
                            <Switch defaultChecked={item.defaultOn} />
                        </div>
                    ))}
                </div>
            </div>

            <PhoneVerifyDialog
                open={verifyDialogOpen}
                onOpenChange={setVerifyDialogOpen}
                initialPhone={user?.phone || ''}
                onVerified={() => setVerifyDialogOpen(false)}
            />
        </motion.div>
    );
}
