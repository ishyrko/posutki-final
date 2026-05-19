'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useUser } from '@/features/auth/hooks';
import { useUpdateProfile } from '../hooks';
import { updateProfileSchema, UpdateProfileData } from '../api';
import { formatUserDisplayName } from '../displayName';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { useEffect } from 'react';

export function ProfileForm() {
    const { data: user } = useUser();
    const { mutate: updateProfile, isPending } = useUpdateProfile();

    const form = useForm<UpdateProfileData>({
        resolver: zodResolver(updateProfileSchema),
        defaultValues: {
            name: '',
            phone: '',
        },
    });

    useEffect(() => {
        if (user) {
            form.reset({
                name: formatUserDisplayName(user),
                phone: user.phone || '',
            });
        }
    }, [user, form]);

    const onSubmit = (data: UpdateProfileData) => {
        updateProfile(data);
    };

    if (!user) return null;

    return (
        <Card>
            <CardHeader>
                <CardTitle>Личные данные</CardTitle>
            </CardHeader>
            <CardContent>
                <Form {...form}>
                    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                        <FormField
                            control={form.control}
                            name="name"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Имя</FormLabel>
                                    <FormControl>
                                        <Input placeholder="Как к вам обращаться" autoComplete="name" {...field} />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <FormField
                            control={form.control}
                            name="phone"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel>Телефон</FormLabel>
                                    <FormControl>
                                        <Input placeholder="Например: +375…" {...field} />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            )}
                        />

                        <Button type="submit" disabled={isPending}>
                            {isPending ? 'Сохранение...' : 'Сохранить'}
                        </Button>
                    </form>
                </Form>
            </CardContent>
        </Card>
    );
}
