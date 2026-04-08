'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { motion } from 'framer-motion';
import { Loader2, Mail, Send, User } from 'lucide-react';
import { submitFeedbackSchema, SubmitFeedbackData } from '../api';
import { useSubmitFeedback } from '../hooks';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Form,
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';

export function ContactForm() {
    const { mutate: submitFeedback, isPending } = useSubmitFeedback();
    const form = useForm<SubmitFeedbackData>({
        resolver: zodResolver(submitFeedbackSchema),
        defaultValues: {
            name: '',
            email: '',
            subject: '',
            message: '',
        },
    });

    const onSubmit = (data: SubmitFeedbackData) => {
        submitFeedback(data, {
            onSuccess: () => {
                form.reset();
            },
        });
    };

    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.4 }}
            className="mx-auto max-w-3xl text-center"
        >
            <h1 className="mb-3 font-display text-3xl font-bold text-foreground">Обратная связь</h1>
            <p className="mb-8 text-muted-foreground">
                Напишите нам, если нужна помощь по объявлениям или работе сервиса.
            </p>

            <Form {...form}>
                <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4 rounded-2xl border border-border bg-card/80 p-3 backdrop-blur sm:p-4">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <FormField
                            control={form.control}
                            name="name"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel className="sr-only">Имя</FormLabel>
                                    <FormControl>
                                        <div className="flex items-center rounded-xl border border-border bg-background px-4 hover:border-primary/30 transition-colors">
                                            <User className="mr-3 h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                            <Input
                                                {...field}
                                                placeholder="Ваше имя"
                                                className="h-12 border-0 bg-transparent px-0 text-foreground placeholder:text-muted-foreground focus-visible:ring-0 focus-visible:ring-offset-0"
                                            />
                                        </div>
                                    </FormControl>
                                    <FormMessage className="text-red-400" />
                                </FormItem>
                            )}
                        />
                        <FormField
                            control={form.control}
                            name="email"
                            render={({ field }) => (
                                <FormItem>
                                    <FormLabel className="sr-only">Email</FormLabel>
                                    <FormControl>
                                        <div className="flex items-center rounded-xl border border-border bg-background px-4 hover:border-primary/30 transition-colors">
                                            <Mail className="mr-3 h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                            <Input
                                                {...field}
                                                type="email"
                                                placeholder="Ваш email"
                                                className="h-12 border-0 bg-transparent px-0 text-foreground placeholder:text-muted-foreground focus-visible:ring-0 focus-visible:ring-offset-0"
                                            />
                                        </div>
                                    </FormControl>
                                    <FormMessage className="text-red-400" />
                                </FormItem>
                            )}
                        />
                    </div>

                    <FormField
                        control={form.control}
                        name="subject"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel className="sr-only">Тема</FormLabel>
                                <FormControl>
                                    <div className="rounded-xl border border-border bg-background px-4 hover:border-primary/30 transition-colors">
                                        <Input
                                            {...field}
                                            placeholder="Тема обращения"
                                            className="h-12 border-0 bg-transparent px-0 text-foreground placeholder:text-muted-foreground focus-visible:ring-0 focus-visible:ring-offset-0"
                                        />
                                    </div>
                                </FormControl>
                                <FormMessage className="text-red-400" />
                            </FormItem>
                        )}
                    />

                    <FormField
                        control={form.control}
                        name="message"
                        render={({ field }) => (
                            <FormItem>
                                <FormLabel className="sr-only">Сообщение</FormLabel>
                                <FormControl>
                                    <div className="rounded-xl border border-border bg-background px-4 py-2 hover:border-primary/30 transition-colors">
                                        <Textarea
                                            {...field}
                                            rows={6}
                                            placeholder="Ваше сообщение"
                                            className="resize-none border-0 bg-transparent px-0 text-foreground placeholder:text-muted-foreground focus-visible:ring-0 focus-visible:ring-offset-0"
                                        />
                                    </div>
                                </FormControl>
                                <FormMessage className="text-red-400" />
                            </FormItem>
                        )}
                    />

                    <Button
                        type="submit"
                        disabled={isPending}
                        className="h-12 w-full rounded-xl border-0 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity"
                    >
                        {isPending ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Отправляем...
                            </>
                        ) : (
                            <>
                                <Send className="mr-2 h-4 w-4" />
                                Отправить
                            </>
                        )}
                    </Button>
                </form>
            </Form>
        </motion.div>
    );
}
