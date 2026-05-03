'use client';

import { Phone } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { PhoneAuthPanel } from './PhoneAuthPanel';

interface PhoneAuthModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initialPhone?: string;
    onSuccess?: () => void;
}

export function PhoneAuthModal({ open, onOpenChange, initialPhone = '', onSuccess }: PhoneAuthModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {open ? (
                <DialogContent className="sm:max-w-md overflow-visible">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Phone className="w-5 h-5" />
                            Вход по SMS
                        </DialogTitle>
                        <DialogDescription>
                            Введите номер телефона для входа или регистрации — мы отправим код в SMS.
                        </DialogDescription>
                    </DialogHeader>
                    <PhoneAuthPanel
                        initialPhone={initialPhone}
                        onAuthenticated={() => {
                            onOpenChange(false);
                            onSuccess?.();
                        }}
                    />
                </DialogContent>
            ) : null}
        </Dialog>
    );
}
