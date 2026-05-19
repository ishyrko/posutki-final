'use client';

import { useEffect, useState } from 'react';
import { Phone, Plus, Trash2, CheckCircle2, Clock, MessageCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import type { User } from '@/features/auth/types';
import { useUpdateProfile } from '@/features/profile/hooks';
import { formatUserDisplayName } from '@/features/profile/displayName';
import { usePhones, useDeletePhone, useUpdatePhoneFlags } from '@/features/phones/hooks';
import { PhoneVerifyDialog } from '@/features/phones/components/PhoneVerifyDialog';

function MessengerCheckboxes({
    hasViber,
    hasWhatsapp,
    disabled,
    onChange,
}: {
    hasViber: boolean;
    hasWhatsapp: boolean;
    disabled?: boolean;
    onChange: (viber: boolean, whatsapp: boolean) => void;
}) {
    return (
        <div className="flex flex-wrap items-center gap-4">
            <label className="flex items-center gap-2 text-sm cursor-pointer">
                <Checkbox
                    checked={hasViber}
                    disabled={disabled}
                    onCheckedChange={(checked) => onChange(checked === true, hasWhatsapp)}
                />
                <span>Viber</span>
            </label>
            <label className="flex items-center gap-2 text-sm cursor-pointer">
                <Checkbox
                    checked={hasWhatsapp}
                    disabled={disabled}
                    onCheckedChange={(checked) => onChange(hasViber, checked === true)}
                />
                <span>WhatsApp</span>
            </label>
        </div>
    );
}

function normalizePhoneDigits(phone: string): string {
    return phone.replace(/\D/g, '');
}

export function ProfileContactsSection({ user }: { user: User | undefined }) {
    const { data: phones = [], isLoading } = usePhones();
    const { mutate: updateProfile, isPending: isSavingContacts } = useUpdateProfile();
    const { mutate: deletePhone, isPending: isDeleting } = useDeletePhone();
    const { mutate: updatePhoneFlags } = useUpdatePhoneFlags();
    const [dialogOpen, setDialogOpen] = useState(false);
    const [telegram, setTelegram] = useState('');

    useEffect(() => {
        if (user) {
            setTelegram(user.telegram ?? '');
        }
    }, [user?.telegram, user?.id]);

    const saveContactSettings = (overrides?: {
        phoneHasViber?: boolean;
        phoneHasWhatsapp?: boolean;
        telegram?: string;
    }) => {
        if (!user) return;
        updateProfile({
            name: formatUserDisplayName(user),
            phone: user.phone,
            telegram: overrides?.telegram ?? telegram,
            phoneHasViber: overrides?.phoneHasViber ?? user.phoneHasViber ?? false,
            phoneHasWhatsapp: overrides?.phoneHasWhatsapp ?? user.phoneHasWhatsapp ?? false,
        });
    };

    const mainPhoneDigits = user?.phone ? normalizePhoneDigits(user.phone) : '';
    const extraPhones = phones.filter((p) => {
        if (!mainPhoneDigits) return true;
        return normalizePhoneDigits(p.phone) !== mainPhoneDigits;
    });
    const verifiedExtraCount = extraPhones.filter((p) => p.isVerified).length;
    const canAddExtra = extraPhones.length < 2;

    return (
        <div className="bg-card rounded-xl p-6 shadow-card mb-6">
            <h3 className="font-semibold text-foreground mb-1 flex items-center gap-2">
                <Phone className="w-4 h-4" />
                Контакты для объявлений
            </h3>
            <p className="text-sm text-muted-foreground mb-5">
                Основной телефон — в блоке выше. Здесь можно добавить до 2 дополнительных номеров и указать мессенджеры.
            </p>

            {user?.phone && user.isPhoneVerified && (
                <div className="rounded-lg border border-border p-4 mb-4">
                    <p className="text-xs text-muted-foreground mb-2">Основной телефон</p>
                    <p className="font-medium text-foreground mb-3 break-all">{user.phone}</p>
                    <MessengerCheckboxes
                        hasViber={user.phoneHasViber ?? false}
                        hasWhatsapp={user.phoneHasWhatsapp ?? false}
                        disabled={isSavingContacts}
                        onChange={(viber, whatsapp) =>
                            saveContactSettings({ phoneHasViber: viber, phoneHasWhatsapp: whatsapp })
                        }
                    />
                </div>
            )}

            {user?.phone && !user.isPhoneVerified && (
                <p className="text-sm text-muted-foreground mb-4">
                    Подтвердите основной телефон в блоке выше, чтобы отметить Viber и WhatsApp.
                </p>
            )}

            <div className="mb-4">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                    <p className="text-sm font-medium text-foreground">Дополнительные телефоны</p>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        className="gap-2 w-full sm:w-auto"
                        disabled={!canAddExtra}
                        onClick={() => setDialogOpen(true)}
                    >
                        <Plus className="w-4 h-4" />
                        Добавить
                    </Button>
                </div>
                {!canAddExtra && (
                    <p className="text-xs text-muted-foreground mb-2">Достигнут лимит: 2 дополнительных номера.</p>
                )}

                {isLoading ? (
                    <p className="text-sm text-muted-foreground">Загрузка...</p>
                ) : extraPhones.length === 0 ? (
                    <p className="text-sm text-muted-foreground">Нет дополнительных телефонов</p>
                ) : (
                    <ul className="divide-y divide-border rounded-lg border border-border">
                        {extraPhones.map((p) => (
                            <li
                                key={p.id}
                                className="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2 mb-2">
                                        <span className="font-medium break-all">{p.phone}</span>
                                        {p.isVerified ? (
                                            <Badge variant="default" className="gap-1">
                                                <CheckCircle2 className="w-3 h-3" />
                                                Подтверждён
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary" className="gap-1">
                                                <Clock className="w-3 h-3" />
                                                Не подтверждён
                                            </Badge>
                                        )}
                                    </div>
                                    {p.isVerified && (
                                        <MessengerCheckboxes
                                            hasViber={p.hasViber}
                                            hasWhatsapp={p.hasWhatsapp}
                                            onChange={(viber, whatsapp) =>
                                                updatePhoneFlags({ id: p.id, hasViber: viber, hasWhatsapp: whatsapp })
                                            }
                                        />
                                    )}
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    disabled={isDeleting}
                                    className="text-destructive hover:text-destructive hover:bg-destructive/10 self-start"
                                    onClick={() => deletePhone(p.id)}
                                >
                                    <Trash2 className="w-4 h-4" />
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
                {verifiedExtraCount > 0 && (
                    <p className="text-xs text-muted-foreground mt-2">
                        Подтверждённые дополнительные номера отображаются в объявлениях вместе с основным.
                    </p>
                )}
            </div>

            <div>
                <label className="text-sm text-muted-foreground mb-1.5 flex items-center gap-1.5">
                    <MessageCircle className="w-3.5 h-3.5" />
                    Telegram
                </label>
                <div className="flex flex-col sm:flex-row gap-2">
                    <Input
                        placeholder="@username"
                        value={telegram}
                        onChange={(e) => setTelegram(e.target.value)}
                    />
                    <Button
                        type="button"
                        variant="outline"
                        disabled={isSavingContacts || !user}
                        onClick={() => saveContactSettings({ telegram })}
                    >
                        {isSavingContacts ? 'Сохранение...' : 'Сохранить'}
                    </Button>
                </div>
                <p className="text-xs text-muted-foreground mt-1.5">Укажите ник без ссылки — например, @myname</p>
            </div>

            <PhoneVerifyDialog open={dialogOpen} onOpenChange={setDialogOpen} />
        </div>
    );
}
