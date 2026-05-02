'use client';

import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import type { User } from '@/features/auth/types';
import {
    individualProfileSchema,
    businessProfileSchema,
    type IndividualProfileFormData,
    type BusinessProfileFormData,
} from '@/features/profile/api';
import { useUpdateIndividualProfile, useUpdateBusinessProfile } from '@/features/profile/hooks';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { FileText } from 'lucide-react';

type SellerTab = 'individual' | 'business';

const SELLER_TAB_STORAGE_KEY = 'kabinetSellerProfileTab';

function readStoredSellerTab(): SellerTab | null {
    if (typeof window === 'undefined') return null;
    const v = window.localStorage.getItem(SELLER_TAB_STORAGE_KEY);
    return v === 'individual' || v === 'business' ? v : null;
}

function persistSellerTab(tab: SellerTab): void {
    if (typeof window === 'undefined') return;
    window.localStorage.setItem(SELLER_TAB_STORAGE_KEY, tab);
}

export function SellerProfileSection({ user }: { user: User | undefined }) {
    const [tab, setTab] = useState<SellerTab>('individual');

    useEffect(() => {
        if (!user) return;

        const hasIndividual = Boolean(user.individualProfile);
        const hasBusiness = Boolean(user.businessProfile);

        if (hasIndividual && !hasBusiness) {
            setTab('individual');
            persistSellerTab('individual');
            return;
        }
        if (hasBusiness && !hasIndividual) {
            setTab('business');
            persistSellerTab('business');
            return;
        }
        if (hasIndividual && hasBusiness) {
            const stored = readStoredSellerTab();
            setTab(stored === 'business' ? 'business' : 'individual');
            return;
        }

        setTab(readStoredSellerTab() ?? 'individual');
    }, [user]);

    const handleTabChange = (value: string) => {
        const next = value as SellerTab;
        setTab(next);
        persistSellerTab(next);
    };

    const { mutate: saveIndividual, isPending: savingIndividual } = useUpdateIndividualProfile();
    const { mutate: saveBusiness, isPending: savingBusiness } = useUpdateBusinessProfile();

    const individualForm = useForm<IndividualProfileFormData>({
        resolver: zodResolver(individualProfileSchema),
        defaultValues: {
            lastName: '',
            firstName: '',
            middleName: '',
            unp: '',
        },
    });

    const businessForm = useForm<BusinessProfileFormData>({
        resolver: zodResolver(businessProfileSchema),
        defaultValues: {
            organizationName: '',
            contactName: '',
            unp: '',
        },
    });

    useEffect(() => {
        if (!user) return;
        individualForm.reset({
            lastName: user.individualProfile?.lastName ?? '',
            firstName: user.individualProfile?.firstName ?? '',
            middleName: user.individualProfile?.middleName ?? '',
            unp: user.individualProfile?.unp ?? '',
        });
        businessForm.reset({
            organizationName: user.businessProfile?.organizationName ?? '',
            contactName: user.businessProfile?.contactName ?? '',
            unp: user.businessProfile?.unp ?? '',
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps -- формы синхронизируются при смене вкладки и данных пользователя
    }, [user, tab]);

    const filled =
        tab === 'individual' ? Boolean(user?.individualProfile) : Boolean(user?.businessProfile);

    return (
        <div className="bg-card rounded-2xl p-6 shadow-card mb-6">
            <div className="flex flex-wrap items-start justify-between gap-2 mb-4">
                <div className="space-y-1">
                    <h3 className="font-semibold text-foreground text-lg flex items-center gap-2">
                        <FileText className="w-4 h-4" />
                        Данные для посуточных объявлений
                    </h3>
                    <p className="text-sm text-muted-foreground max-w-xl">
                        Можно указать только один тип реквизитов. При сохранении как физлица данные
                        организации удаляются; при сохранении организации — данные физлица.
                    </p>
                </div>
                <Badge variant={filled ? 'default' : 'secondary'} className="shrink-0">
                    {filled ? 'Заполнено' : 'Не заполнено'}
                </Badge>
            </div>

            <Tabs
                value={tab}
                onValueChange={handleTabChange}
                className="w-full"
            >
                <TabsList className="mb-4 w-full max-w-md grid grid-cols-2 h-auto p-1">
                    <TabsTrigger value="individual" className="py-2">
                        Физическое лицо
                    </TabsTrigger>
                    <TabsTrigger value="business" className="py-2">
                        Организация
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="individual" className="mt-0 space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Отдельно от имени в аккаунте. Сохранение заменит реквизиты организации, если
                        они были указаны ранее.
                    </p>
                    <form
                        onSubmit={individualForm.handleSubmit((data) => saveIndividual(data))}
                        className="space-y-4"
                    >
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm text-muted-foreground mb-1.5 block">
                                    Фамилия
                                </label>
                                <Input {...individualForm.register('lastName')} autoComplete="family-name" />
                                {individualForm.formState.errors.lastName && (
                                    <p className="text-destructive text-xs mt-1">
                                        {individualForm.formState.errors.lastName.message}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm text-muted-foreground mb-1.5 block">Имя</label>
                                <Input {...individualForm.register('firstName')} autoComplete="given-name" />
                                {individualForm.formState.errors.firstName && (
                                    <p className="text-destructive text-xs mt-1">
                                        {individualForm.formState.errors.firstName.message}
                                    </p>
                                )}
                            </div>
                        </div>
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">
                                Отчество (если есть)
                            </label>
                            <Input
                                {...individualForm.register('middleName')}
                                placeholder="Необязательно"
                                autoComplete="additional-name"
                            />
                            {individualForm.formState.errors.middleName && (
                                <p className="text-destructive text-xs mt-1">
                                    {individualForm.formState.errors.middleName.message}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">
                                УНП{' '}
                                <span className="text-muted-foreground/80">
                                    (9 символов: цифры и латиница A–Z)
                                </span>
                            </label>
                            <Input
                                {...individualForm.register('unp')}
                                className="font-mono uppercase"
                                maxLength={9}
                                autoComplete="off"
                            />
                            {individualForm.formState.errors.unp && (
                                <p className="text-destructive text-xs mt-1">
                                    {individualForm.formState.errors.unp.message}
                                </p>
                            )}
                        </div>
                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                disabled={savingIndividual}
                                className="bg-gradient-primary text-primary-foreground border-0"
                            >
                                {savingIndividual ? 'Сохранение...' : 'Сохранить данные физлица'}
                            </Button>
                        </div>
                    </form>
                </TabsContent>

                <TabsContent value="business" className="mt-0 space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Сохранение заменит реквизиты физлица, если они были указаны ранее.
                    </p>
                    <form
                        onSubmit={businessForm.handleSubmit((data) => saveBusiness(data))}
                        className="space-y-4"
                    >
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">
                                Наименование организации
                            </label>
                            <Input {...businessForm.register('organizationName')} autoComplete="organization" />
                            {businessForm.formState.errors.organizationName && (
                                <p className="text-destructive text-xs mt-1">
                                    {businessForm.formState.errors.organizationName.message}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">
                                Имя контакта
                            </label>
                            <Input {...businessForm.register('contactName')} autoComplete="name" />
                            {businessForm.formState.errors.contactName && (
                                <p className="text-destructive text-xs mt-1">
                                    {businessForm.formState.errors.contactName.message}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className="text-sm text-muted-foreground mb-1.5 block">
                                УНП организации{' '}
                                <span className="text-muted-foreground/80">
                                    (9 символов: цифры и латиница A–Z)
                                </span>
                            </label>
                            <Input
                                {...businessForm.register('unp')}
                                className="font-mono uppercase"
                                maxLength={9}
                                autoComplete="off"
                            />
                            {businessForm.formState.errors.unp && (
                                <p className="text-destructive text-xs mt-1">
                                    {businessForm.formState.errors.unp.message}
                                </p>
                            )}
                        </div>
                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                disabled={savingBusiness}
                                className="bg-gradient-primary text-primary-foreground border-0"
                            >
                                {savingBusiness ? 'Сохранение...' : 'Сохранить данные организации'}
                            </Button>
                        </div>
                    </form>
                </TabsContent>
            </Tabs>
        </div>
    );
}
