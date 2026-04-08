import type { Metadata, Viewport } from "next";
import { Inter } from "next/font/google";
import Script from "next/script";
import "./globals.css";
import QueryProvider from "@/providers/QueryProvider";
import { Toaster } from "@/components/ui/sonner";
import CookieBanner from "@/components/CookieBanner";

const inter = Inter({ subsets: ["latin"] });

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  viewportFit: "cover",
};

export const metadata: Metadata = {
  title: "RNB.by — Недвижимость Беларуси",
  description: "Тысячи объектов недвижимости по всей Беларуси. Покупка, продажа и аренда — быстро, удобно и безопасно.",
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
      <body className={inter.className}>
        <QueryProvider>
          {children}
          <Toaster />
          <CookieBanner />
        </QueryProvider>
      </body>
    </html>
  );
}
