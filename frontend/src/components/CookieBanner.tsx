"use client";

import { useState, useSyncExternalStore } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";

const COOKIE_CONSENT_KEY = "cookie_consent";

export default function CookieBanner() {
  const [accepted, setAccepted] = useState(false);
  const isMounted = useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );
  const isVisible = isMounted && !accepted && window.localStorage.getItem(COOKIE_CONSENT_KEY) !== "accepted";

  const handleAccept = () => {
    window.localStorage.setItem(COOKIE_CONSENT_KEY, "accepted");
    setAccepted(true);
  };

  if (!isVisible) {
    return null;
  }

  return (
    <div className="fixed inset-x-0 bottom-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
      <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
        <p className="text-sm text-foreground">
          Мы используем файлы cookie для улучшения работы сайта. Продолжая использовать сайт, вы соглашаетесь с нашей{" "}
          <Link href="/politika-konfidentsialnosti" className="underline underline-offset-4 hover:text-primary">
            политикой конфиденциальности
          </Link>
          .
        </p>
        <Button onClick={handleAccept}>Принять</Button>
      </div>
    </div>
  );
}
