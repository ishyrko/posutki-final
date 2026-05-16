import type { Metadata, Viewport } from "next";
import Script from "next/script";
import { getSiteOrigin } from "@/lib/site-url";
import "./globals.css";
import QueryProvider from "@/providers/QueryProvider";
import { Toaster } from "@/components/ui/sonner";
import CookieBanner from "@/components/CookieBanner";
import { CurrencyProvider } from "@/context/CurrencyContext";

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  viewportFit: "cover",
};

export const metadata: Metadata = {
  metadataBase: new URL(getSiteOrigin()),
  title:
    "Квартиры и дома на сутки в Беларуси - посуточная аренда в Минске и других городах",
  description:
  "Снимайте квартиры и дома на сутки в Беларуси напрямую от владельцев на Posutki.by. Минск, Гродно, Брест, Витебск, Гомель, Могилёв — актуальные объявления, удобный поиск по городу, типу жилья и количеству гостей.",
  icons: {
    icon: [
      { url: "/favicon.ico" },
      { url: "/favicon-16x16.png", sizes: "16x16", type: "image/png" },
      { url: "/favicon-32x32.png", sizes: "32x32", type: "image/png" },
    ],
    apple: [{ url: "/apple-touch-icon.png", sizes: "180x180", type: "image/png" }],
  },
  manifest: "/site.webmanifest",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const performanceMeasureGuard = `
    (function () {
      if (typeof window === 'undefined' || typeof window.performance?.measure !== 'function') return;
      var originalMeasure = window.performance.measure.bind(window.performance);
      window.performance.measure = function () {
        try {
          return originalMeasure.apply(window.performance, arguments);
        } catch (error) {
          if (/(negative time stamp|does not exist)/i.test(String(error && error.message ? error.message : error))) {
            return;
          }
          throw error;
        }
      };
    })();
  `;

  return (
    <html lang="ru">
      <head>
        <Script
          id="performance-measure-guard"
          strategy="beforeInteractive"
          dangerouslySetInnerHTML={{ __html: performanceMeasureGuard }}
        />
      </head>
      <body className="font-body antialiased">
        <QueryProvider>
          <CurrencyProvider>
            {children}
          </CurrencyProvider>
          <Toaster />
          <CookieBanner />
        </QueryProvider>
      </body>
    </html>
  );
}
