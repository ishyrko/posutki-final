'use client';

import { useCallback, useEffect, useState } from 'react';
import { CheckCircle2, Loader2, Phone } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { useRequestSmsCode, useVerifySmsCode } from '../hooks';

interface PhoneAuthModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initialPhone?: string;
    onSuccess?: () => void;
}

const RESEND_COOLDOWN = 60;

export function PhoneAuthModal({
    open,
    onOpenChange,
    initialPhone = '',
    onSuccess,
}: PhoneAuthModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            {open ? (
                <PhoneAuthModalContent
                    initialPhone={initialPhone}
                    onOpenChange={onOpenChange}
                    onSuccess={onSuccess}
                />
            ) : null}
        </Dialog>
    );
}

function PhoneAuthModalContent({
    initialPhone,
    onOpenChange,
    onSuccess,
}: {
    initialPhone: string;
    onOpenChange: (open: boolean) => void;
    onSuccess?: () => void;
}) {
    const [step, setStep] = useState<'phone' | 'code'>('phone');
    const [phone, setPhone] = useState(initialPhone);
    const [code, setCode] = useState('');
    const [countdown, setCountdown] = useState(0);

    const { mutateAsync: requestCode, isPending: requesting } = useRequestSmsCode();
    const { mutateAsync: verifyCode, isPending: verifying } = useVerifySmsCode();

    useEffect(() => {
        if (countdown <= 0) return;
        const timer = window.setInterval(() => setCountdown((value) => value - 1), 1000);
        return () => window.clearInterval(timer);
    }, [countdown]);

    const handleRequestCode = useCallback(async () => {
        if (!phone.trim()) return;

        await requestCode(phone.trim());
        setStep('code');
        setCountdown(RESEND_COOLDOWN);
    }, [phone, requestCode]);

    const handleVerify = useCallback(async () => {
        if (code.length !== 6) return;

        await verifyCode({ phone: phone.trim(), code });
        onOpenChange(false);
        onSuccess?.();
    }, [code, onOpenChange, onSuccess, phone, verifyCode]);

    const handleResend = useCallback(async () => {
        if (!phone.trim()) return;

        await requestCode(phone.trim());
        setCode('');
        setCountdown(RESEND_COOLDOWN);
    }, [phone, requestCode]);

    return (
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Phone className="w-5 h-5" />
                        Вход по SMS
                    </DialogTitle>
                    <DialogDescription>
                        {step === 'phone'
                            ? 'Введите номер телефона для входа или регистрации'
                            : `Введите код из SMS, отправленный на ${phone}`}
                    </DialogDescription>
                </DialogHeader>

                {step === 'phone' ? (
                    <div className="space-y-4">
                        <Input
                            type="tel"
                            value={phone}
                            onChange={(event) => setPhone(event.target.value)}
                            placeholder="Например: +375 29 123-45-67"
                            maxLength={20}
                        />
                        <Button
                            onClick={handleRequestCode}
                            disabled={!phone.trim() || requesting}
                            className="w-full bg-gradient-primary text-primary-foreground border-0"
                        >
                            {requesting ? (
                                <>
                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                    Отправка...
                                </>
                            ) : (
                                'Получить код'
                            )}
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-4">
                        <div className="flex justify-center">
                            <InputOTP maxLength={6} value={code} onChange={setCode}>
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
                                <>
                                    <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                                    Проверка...
                                </>
                            ) : (
                                <>
                                    <CheckCircle2 className="w-4 h-4 mr-2" />
                                    Подтвердить
                                </>
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
                            onClick={() => {
                                setStep('phone');
                                setCode('');
                            }}
                            className="text-sm text-muted-foreground hover:text-foreground w-full text-center"
                        >
                            Изменить номер
                        </button>
                    </div>
                )}
            </DialogContent>
    );
}
