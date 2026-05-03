'use client';

import type { ReactNode } from 'react';
import { useCallback, useEffect, useRef, useState } from 'react';
import ReCAPTCHA from 'react-google-recaptcha';
import { CheckCircle2, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { RecaptchaWidget } from '@/components/RecaptchaWidget';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { useRequestSmsCode, useVerifySmsCode } from '../hooks';

const RESEND_COOLDOWN = 60;

export interface PhoneAuthPanelProps {
    onAuthenticated: () => void;
    initialPhone?: string;
    /** Для регистрации по SMS: чекбокс «согласен» должен быть включён */
    consentAccepted?: boolean;
    /** Блок согласия (показывается перед кнопкой «Получить код») */
    consentSlot?: ReactNode;
    className?: string;
}

export function PhoneAuthPanel({
    onAuthenticated,
    initialPhone = '',
    consentAccepted = true,
    consentSlot,
    className,
}: PhoneAuthPanelProps) {
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
        if (!phone.trim() || !consentAccepted) return;

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
    }, [phone, phoneCaptchaToken, requestCode, siteKey, consentAccepted]);

    const handleVerify = useCallback(async () => {
        if (code.length !== 6) return;

        await verifyCode({ phone: phone.trim(), code });
        onAuthenticated();
    }, [code, onAuthenticated, phone, verifyCode]);

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
        <div className={cn('space-y-4', className)}>
            {step === 'phone' ? (
                <>
                    <div className="space-y-2">
                        <Label htmlFor="auth-phone">Номер телефона</Label>
                        <Input
                            id="auth-phone"
                            type="tel"
                            value={phone}
                            onChange={(event) => setPhone(event.target.value)}
                            placeholder="+375 (29) 123-45-67"
                            maxLength={20}
                            className="h-11"
                        />
                    </div>
                    {siteKey ? (
                        <RecaptchaWidget
                            recaptchaRef={phoneCaptchaRef}
                            onChange={setPhoneCaptchaToken}
                            onExpired={() => setPhoneCaptchaToken(null)}
                        />
                    ) : null}
                    {consentSlot}
                    <Button
                        type="button"
                        onClick={() => void handleRequestCode()}
                        disabled={
                            !phone.trim() ||
                            !consentAccepted ||
                            requesting ||
                            (Boolean(siteKey) && !phoneCaptchaToken)
                        }
                        className="w-full h-11"
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
                </>
            ) : (
                <>
                    <div className="space-y-2">
                        <Label htmlFor="auth-sms-code">Код из SMS</Label>
                        <div className="flex justify-center py-1">
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
                        <p className="text-xs text-muted-foreground text-center">Код отправлен на {phone}</p>
                    </div>

                    <Button
                        type="button"
                        onClick={() => void handleVerify()}
                        disabled={code.length !== 6 || verifying}
                        className="w-full h-11"
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
                                    onClick={() => void handleResend()}
                                    disabled={requesting || (Boolean(siteKey) && !resendCaptchaToken)}
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
                </>
            )}
        </div>
    );
}
