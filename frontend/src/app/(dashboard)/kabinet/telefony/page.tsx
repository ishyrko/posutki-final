'use client';

import { useState } from 'react';
import { motion } from 'framer-motion';
import { Phone, Plus, Trash2, CheckCircle2, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { usePhones, useDeletePhone } from '@/features/phones/hooks';
import { PhoneVerifyDialog } from '@/features/phones/components/PhoneVerifyDialog';

export default function PhonesPage() {
    const { data: phones = [], isLoading } = usePhones();
    const { mutate: deletePhoneMut, isPending: deleting } = useDeletePhone();
    const [dialogOpen, setDialogOpen] = useState(false);

    return (
        <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
            className="max-w-2xl w-full min-w-0"
        >
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <h1 className="font-display text-2xl font-bold text-foreground">
                    Мои телефоны
                </h1>
                <Button onClick={() => setDialogOpen(true)} className="gap-2 w-full sm:w-auto">
                    <Plus className="w-4 h-4" />
                    Добавить
                </Button>
            </div>

            <p className="text-sm text-muted-foreground mb-6">
                Подтверждённые телефоны можно указывать в объявлениях как контактные.
            </p>

            <div className="bg-card rounded-xl shadow-card">
                {isLoading ? (
                    <div className="p-8 text-center text-muted-foreground">Загрузка...</div>
                ) : phones.length === 0 ? (
                    <div className="p-8 text-center">
                        <Phone className="w-10 h-10 mx-auto text-muted-foreground/50 mb-3" />
                        <p className="text-muted-foreground">Нет добавленных телефонов</p>
                        <p className="text-sm text-muted-foreground mt-1">
                            Добавьте телефон, чтобы использовать его в объявлениях
                        </p>
                    </div>
                ) : (
                    <ul className="divide-y divide-border">
                        {phones.map((p) => (
                            <li key={p.id} className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 py-4 min-w-0">
                                <div className="flex items-center gap-3 min-w-0 flex-wrap">
                                    <Phone className="w-4 h-4 text-muted-foreground shrink-0" />
                                    <span className="font-medium text-foreground break-all">{p.phone}</span>
                                    {p.isVerified ? (
                                        <Badge variant="default" className="gap-1 max-w-full">
                                            <CheckCircle2 className="w-3 h-3" />
                                            Подтверждён
                                        </Badge>
                                    ) : (
                                        <Badge variant="secondary" className="gap-1 max-w-full">
                                            <Clock className="w-3 h-3" />
                                            Не подтверждён
                                        </Badge>
                                    )}
                                </div>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => deletePhoneMut(p.id)}
                                    disabled={deleting}
                                    className="text-destructive hover:text-destructive hover:bg-destructive/10 self-start sm:self-auto"
                                >
                                    <Trash2 className="w-4 h-4" />
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <PhoneVerifyDialog
                open={dialogOpen}
                onOpenChange={setDialogOpen}
            />
        </motion.div>
    );
}
