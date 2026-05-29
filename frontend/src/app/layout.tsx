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
      { url: "/favicon-120x120.png", sizes: "120x120", type: "image/png" },
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

        {/* Google tag (gtag.js) */}
        <Script
            src="https://www.googletagmanager.com/gtag/js?id=G-M7RYVGGSKK"
            strategy="afterInteractive"
        />
        <Script
            id="google-analytics-gtag"
            strategy="afterInteractive"
            dangerouslySetInnerHTML={{
              __html: `
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-M7RYVGGSKK');
`,
            }}
        />
        {/* /Google tag (gtag.js) */}
        {/* Yandex.Metrika counter */}
        <Script
            id="yandex-metrika"
            strategy="afterInteractive"
            dangerouslySetInnerHTML={{
              __html: `
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=109330583', 'ym');

    ym(109330583, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
`,
            }}
        />
        <noscript>
          <div>
            <img
                src="https://mc.yandex.ru/watch/109330583"
                style={{ position: "absolute", left: "-9999px" }}
                alt=""
            />
          </div>
        </noscript>
        {/* /Yandex.Metrika counter */}
      </body>
    </html>
  );
}
