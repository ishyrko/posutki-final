'use client';

import { useCallback, useEffect, useRef, useState } from 'react';
import ReCAPTCHA from 'react-google-recaptcha';
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
import { RecaptchaWidget } from '@/components/RecaptchaWidget';
import { toast } from 'sonner';
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

    const siteKey = process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY?.trim() ?? '';
    const phoneCaptchaRef = useRef<ReCAPTCHA | null>(null);
    const [phoneCaptchaToken, setPhoneCaptchaToken] = useState<string | null>(null);
    const resendCaptchaRef = useRef<ReCAPTCHA | null>(null);
    const [resendCaptchaToken, setResendCaptchaToken] = useState<string | null>(null);
    const [resendWidgetId, setResendWidgetId] = useState(0);

    const { mutateAsync: requestCode, isPending: requesting } = useRequestSmsCode();
    const { mutateAsync: verifyCode, isPending: verifying } = useVerifySmsCode();

    useEffect(() => {
        if (countdown <= 0) return;
        const timer = window.setInterval(() => setCountdown((value) => value - 1), 1000);
        return () => window.clearInterval(timer);
    }, [countdown]);

    const handleRequestCode = useCallback(async () => {
        if (!phone.trim()) return;

        if (siteKey && !phoneCaptchaToken) {
            toast.error('Подтвердите, что вы не робот');
            return;
        }

        const recaptchaToken = phoneCaptchaToken ?? '';
        await requestCode({ phone: phone.trim(), recaptchaToken });
        phoneCaptchaRef.current?.reset();
        setPhoneCaptchaToken(null);
        setStep('code');
        setCountdown(RESEND_COOLDOWN);
        setResendWidgetId((id) => id + 1);
        setResendCaptchaToken(null);
    }, [phone, phoneCaptchaToken, requestCode, siteKey]);

    const handleVerify = useCallback(async () => {
        if (code.length !== 6) return;

        await verifyCode({ phone: phone.trim(), code });
        onOpenChange(false);
        onSuccess?.();
    }, [code, onOpenChange, onSuccess, phone, verifyCode]);

    const handleResend = useCallback(async () => {
        if (!phone.trim()) return;

        if (siteKey && !resendCaptchaToken) {
            toast.error('Подтвердите, что вы не робот');
            return;
        }

        const recaptchaToken = resendCaptchaToken ?? '';
        await requestCode({ phone: phone.trim(), recaptchaToken });
        resendCaptchaRef.current?.reset();
        setResendCaptchaToken(null);
        setResendWidgetId((id) => id + 1);
        setCode('');
        setCountdown(RESEND_COOLDOWN);
    }, [phone, requestCode, resendCaptchaToken, siteKey]);

    const goBackToPhone = useCallback(() => {
        setStep('phone');
        setCode('');
        setPhoneCaptchaToken(null);
        phoneCaptchaRef.current?.reset();
    }, []);

    return (
        <DialogContent className="sm:max-w-md overflow-visible">
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
                    {siteKey ? (
                        <RecaptchaWidget
                            recaptchaRef={phoneCaptchaRef}
                            onChange={setPhoneCaptchaToken}
                            onExpired={() => setPhoneCaptchaToken(null)}
                        />
                    ) : null}
                    <Button
                        onClick={handleRequestCode}
                        disabled={
                            !phone.trim() ||
                            requesting ||
                            (Boolean(siteKey) && !phoneCaptchaToken)
                        }
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

                    <div className="text-center space-y-3">
                        {countdown > 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Отправить повторно через {countdown} сек
                            </p>
                        ) : (
                            <>
                                {siteKey ? (
                                    <RecaptchaWidget
                                        key={`resend-${resendWidgetId}`}
                                        recaptchaRef={resendCaptchaRef}
                                        onChange={setResendCaptchaToken}
                                        onExpired={() => setResendCaptchaToken(null)}
                                    />
                                ) : null}
                                <button
                                    type="button"
                                    onClick={handleResend}
                                    disabled={
                                        requesting || (Boolean(siteKey) && !resendCaptchaToken)
                                    }
                                    className="text-sm text-primary hover:underline disabled:pointer-events-none disabled:opacity-50"
                                >
                                    Отправить код повторно
                                </button>
                            </>
                        )}
                    </div>

                    <button
                        type="button"
                        onClick={goBackToPhone}
                        className="text-sm text-muted-foreground hover:text-foreground w-full text-center"
                    >
                        Изменить номер
                    </button>
                </div>
            )}
        </DialogContent>
    );
}
