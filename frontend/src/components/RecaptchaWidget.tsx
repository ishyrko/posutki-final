'use client';

import type { RefObject } from 'react';
import ReCAPTCHA from 'react-google-recaptcha';
import { useRecaptchaPointerEventsFix } from '@/lib/recaptcha-pointer-events';

type RecaptchaWidgetProps = {
    recaptchaRef: RefObject<ReCAPTCHA | null>;
    onChange: (token: string | null) => void;
    onExpired?: () => void;
};

export function RecaptchaWidget({ recaptchaRef, onChange, onExpired }: RecaptchaWidgetProps) {
    const siteKey = process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY?.trim() ?? '';
    useRecaptchaPointerEventsFix(Boolean(siteKey));
    if (!siteKey) {
        return null;
    }

    return (
        <div className="flex min-h-[78px] justify-center overflow-x-auto">
            <ReCAPTCHA
                ref={recaptchaRef}
                sitekey={siteKey}
                onChange={onChange}
                onExpired={onExpired}
                theme="light"
            />
        </div>
    );
}
