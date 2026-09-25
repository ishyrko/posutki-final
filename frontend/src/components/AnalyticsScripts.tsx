"use client";

import { useLayoutEffect } from "react";
import { GA_MEASUREMENT_ID } from "@/lib/analytics-ids";
import { YANDEX_METRIKA_COUNTER_ID } from "@/lib/metrika";

const ANALYTICS_LOAD_TIMEOUT_MS = 3500;
const GTAG_URL = `https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`;
const METRIKA_TAG_URL = `https://mc.yandex.ru/metrika/tag.js?id=${YANDEX_METRIKA_COUNTER_ID}`;

let analyticsScriptsLoaded = false;
let analyticsLoadScheduled = false;

function installAnalyticsStubs(): void {
  if (typeof window === "undefined") {
    return;
  }

  window.dataLayer = window.dataLayer || [];

  if (typeof window.gtag !== "function") {
    window.gtag = function gtag(...args: unknown[]) {
      window.dataLayer?.push(args);
    };
  }

  if (typeof window.ym !== "function") {
    const ymStub = function (...args: unknown[]) {
      (ymStub.a = ymStub.a || []).push(args);
    } as Window["ym"] & { a?: unknown[][] };
    window.ym = ymStub;
  }
}

function loadExternalScript(src: string): Promise<void> {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) {
      resolve();
      return;
    }

    const script = document.createElement("script");
    script.src = src;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => reject(new Error(`Failed to load script: ${src}`));
    document.head.appendChild(script);
  });
}

function initYandexMetrika(): void {
  window.ym?.(YANDEX_METRIKA_COUNTER_ID, "init", {
    ssr: true,
    webvisor: true,
    clickmap: true,
    ecommerce: "dataLayer",
    referrer: document.referrer,
    url: location.href,
    accurateTrackBounce: true,
    trackLinks: true,
  });
}

function initGoogleAnalytics(): void {
  window.gtag?.("js", new Date());
  window.gtag?.("config", GA_MEASUREMENT_ID);
}

async function loadAnalyticsScripts(): Promise<void> {
  if (analyticsScriptsLoaded) {
    return;
  }

  analyticsScriptsLoaded = true;
  installAnalyticsStubs();

  try {
    await Promise.all([
      loadExternalScript(GTAG_URL),
      loadExternalScript(METRIKA_TAG_URL),
    ]);
    initGoogleAnalytics();
  } catch (error) {
    analyticsScriptsLoaded = false;
    console.error("Failed to load analytics scripts", error);
  }
}

function scheduleDeferredAnalyticsLoad(): () => void {
  if (analyticsScriptsLoaded || analyticsLoadScheduled) {
    return () => {};
  }

  analyticsLoadScheduled = true;

  let triggered = false;

  const load = () => {
    if (triggered) {
      return;
    }
    triggered = true;
    cleanup();
    void loadAnalyticsScripts();
  };

  const timer = window.setTimeout(load, ANALYTICS_LOAD_TIMEOUT_MS);

  const idleId =
    typeof window.requestIdleCallback === "function"
      ? window.requestIdleCallback(load, { timeout: ANALYTICS_LOAD_TIMEOUT_MS })
      : null;

  const gestureOptions: AddEventListenerOptions = { once: true, passive: true, capture: true };
  const onGesture = () => load();

  window.addEventListener("pointerdown", onGesture, gestureOptions);
  window.addEventListener("keydown", onGesture, gestureOptions);
  window.addEventListener("scroll", onGesture, gestureOptions);

  function cleanup() {
    window.clearTimeout(timer);
    if (idleId !== null && typeof window.cancelIdleCallback === "function") {
      window.cancelIdleCallback(idleId);
    }
    window.removeEventListener("pointerdown", onGesture, gestureOptions);
    window.removeEventListener("keydown", onGesture, gestureOptions);
    window.removeEventListener("scroll", onGesture, gestureOptions);
  }

  return cleanup;
}

export default function AnalyticsScripts() {
  useLayoutEffect(() => {
    installAnalyticsStubs();
    initYandexMetrika();
    return scheduleDeferredAnalyticsLoad();
  }, []);

  return null;
}
