'use client';

import { useState, useEffect, useCallback } from 'react';
import { Phone, Loader2, CheckCircle2 } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { useRequestVerification, useVerifyPhone } from '../hooks';

interface PhoneVerifyDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initialPhone?: string;
    onVerified?: (phone: string) => void;
}

const RESEND_COOLDOWN = 60;

export function PhoneVerifyDialog({ open, onOpenChange, initialPhone = '', onVerified }: PhoneVerifyDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {open ? (
                <PhoneVerifyDialogContent
                    initialPhone={initialPhone}
                    onOpenChange={onOpenChange}
                    onVerified={onVerified}
                />
            ) : null}
        </Dialog>
    );
}

function PhoneVerifyDialogContent({
    initialPhone,
    onOpenChange,
    onVerified,
}: {
    initialPhone: string;
    onOpenChange: (open: boolean) => void;
    onVerified?: (phone: string) => void;
}) {
    const [step, setStep] = useState<'phone' | 'code'>('phone');
    const [phone, setPhone] = useState(initialPhone);
    const [code, setCode] = useState('');
    const [countdown, setCountdown] = useState(0);

    const { mutateAsync: requestCode, isPending: requesting } = useRequestVerification();
    const { mutateAsync: verify, isPending: verifying } = useVerifyPhone();

    useEffect(() => {
        if (countdown <= 0) return;
        const timer = setInterval(() => setCountdown((c) => c - 1), 1000);
        return () => clearInterval(timer);
    }, [countdown]);

    const handleRequestCode = useCallback(async () => {
        if (!phone.trim()) return;
        try {
            await requestCode(phone.trim());
            setStep('code');
            setCountdown(RESEND_COOLDOWN);
        } catch {
            // error toast handled by hook
        }
    }, [phone, requestCode]);

    const handleVerify = useCallback(async () => {
        if (code.length !== 6) return;
        try {
            await verify({ phone: phone.trim(), code });
            onVerified?.(phone.trim());
            onOpenChange(false);
        } catch {
            setCode('');
        }
    }, [code, phone, verify, onVerified, onOpenChange]);

    const handleResend = useCallback(async () => {
        try {
            await requestCode(phone.trim());
            setCountdown(RESEND_COOLDOWN);
            setCode('');
        } catch {
            // handled by hook
        }
    }, [phone, requestCode]);

    return (
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Phone className="w-5 h-5" />
                        Подтверждение телефона
                    </DialogTitle>
                    <DialogDescription>
                        {step === 'phone'
                            ? 'Введите номер телефона для подтверждения'
                            : `Введите код из SMS, отправленный на ${phone}`}
                    </DialogDescription>
                </DialogHeader>

                {step === 'phone' ? (
                    <div className="space-y-4">
                        <Input
                            type="tel"
                            value={phone}
                            onChange={(e) => setPhone(e.target.value)}
                            placeholder="Например: +375 29 123-45-67"
                            maxLength={20}
                        />
                        <Button
                            onClick={handleRequestCode}
                            disabled={!phone.trim() || requesting}
                            className="w-full bg-gradient-primary text-primary-foreground border-0"
                        >
                            {requesting ? (
                                <><Loader2 className="w-4 h-4 mr-2 animate-spin" />Отправка...</>
                            ) : (
                                'Получить код'
                            )}
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="flex justify-center">
                            <InputOTP
                                maxLength={6}
                                value={code}
                                onChange={setCode}
                            >
                                <InputOTPGroup>
                                    <InputOTPSlot index={0} />
                                    <InputOTPSlot index={1} />
                                    <InputOTPSlot index={2} />
                                    <InputOTPSlot index={3} />
                                    <InputOTPSlot index={4} />
                                    <InputOTPSlot index={5} />
                                </InputOTPGroup>
                            </InputOTP>
                        </div>

                        <Button
                            onClick={handleVerify}
                            disabled={code.length !== 6 || verifying}
                            className="w-full bg-gradient-primary text-primary-foreground border-0"
                        >
                            {verifying ? (
                                <><Loader2 className="w-4 h-4 mr-2 animate-spin" />Проверка...</>
                            ) : (
                                <><CheckCircle2 className="w-4 h-4 mr-2" />Подтвердить</>
                            )}
                        </Button>

                        <div className="text-center">
                            {countdown > 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Отправить повторно через {countdown} сек
                                </p>
                            ) : (
                                <button
                                    onClick={handleResend}
                                    disabled={requesting}
                                    className="text-sm text-primary hover:underline"
                                >
                                    Отправить код повторно
                                </button>
                            )}
                        </div>

                        <button
                            onClick={() => { setStep('phone'); setCode(''); }}
                            className="text-sm text-muted-foreground hover:text-foreground w-full text-center"
                        >
                            Изменить номер
                        </button>
                    </div>
                )}
            </DialogContent>
    );
}
