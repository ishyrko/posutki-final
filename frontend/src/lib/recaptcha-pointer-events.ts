'use client';

import { useEffect } from 'react';

const RECAPTCHA_IFRAME_SELECTOR = 'iframe[src*="google.com/recaptcha"]';
const RECAPTCHA_CHALLENGE_SELECTOR = 'div[style*="z-index: 2000000000"]';

export function isRecaptchaElement(target: EventTarget | null): boolean {
    if (!(target instanceof Element)) {
        return false;
    }

    return Boolean(
        target.closest(RECAPTCHA_IFRAME_SELECTOR)
        || target.closest(RECAPTCHA_CHALLENGE_SELECTOR)
        || target.closest('[class*="grecaptcha"]'),
    );
}

function enablePointerEventsForRecaptchaNodes(): void {
    const nodes = document.querySelectorAll<HTMLElement>(
        `${RECAPTCHA_IFRAME_SELECTOR}, ${RECAPTCHA_CHALLENGE_SELECTOR}`,
    );

    nodes.forEach((node) => {
        let el: HTMLElement | null = node;
        while (el && el !== document.body) {
            el.style.pointerEvents = 'auto';
            el = el.parentElement;
        }
    });
}

/** Radix Dialog sets `body { pointer-events: none }`; reCAPTCHA challenge is appended to body and must stay clickable. */
export function useRecaptchaPointerEventsFix(active = true): void {
    useEffect(() => {
        if (!active || typeof document === 'undefined') {
            return;
        }

        enablePointerEventsForRecaptchaNodes();

        const observer = new MutationObserver(() => {
            enablePointerEventsForRecaptchaNodes();
        });

        observer.observe(document.body, { childList: true, subtree: true });

        return () => observer.disconnect();
    }, [active]);
}
