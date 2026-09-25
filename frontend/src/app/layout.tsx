import type { Metadata, Viewport } from "next";
import Script from "next/script";
import { getSiteOrigin } from "@/lib/site-url";
import { buildOpenGraphMeta } from "@/lib/seo/open-graph";
import { inter } from "@/lib/fonts";
import "./globals.css";
import QueryProvider from "@/providers/QueryProvider";
import { Toaster } from "@/components/ui/sonner";
import CookieBanner from "@/components/CookieBanner";
import { CurrencyProvider } from "@/context/CurrencyContext";
import { YANDEX_METRIKA_COUNTER_ID } from "@/lib/metrika";
import DeployVersionGuard from "@/components/DeployVersionGuard";
import AnalyticsScripts from "@/components/AnalyticsScripts";
import { fetchExchangeRates } from "@/lib/exchange-rates-server";

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  viewportFit: "cover",
};

const defaultTitle =
  "Квартиры и усадьбы на сутки в Беларуси - посуточная аренда в Минске и других городах";
const defaultDescription =
  "Снимайте квартиры и усадьбы на сутки в Беларуси напрямую от владельцев на Posutki.by. Минск, Гродно, Брест, Витебск, Гомель, Могилёв — актуальные объявления, удобный поиск по городу, типу жилья и количеству гостей.";

export const metadata: Metadata = {
  metadataBase: new URL(getSiteOrigin()),
  title: defaultTitle,
  description: defaultDescription,
  ...buildOpenGraphMeta({
    title: defaultTitle,
    description: defaultDescription,
    path: "/",
  }),
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

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const initialExchangeRates = await fetchExchangeRates();
  const staleChunkReloadGuard = `
    (function () {
      var key = 'posutki-chunk-reload';
      function hardReload() {
        function navigate() {
          try {
            var url = new URL(window.location.href);
            url.searchParams.set('_cb', String(Date.now()));
            window.location.replace(url.toString());
          } catch (e) {
            window.location.reload();
          }
        }
        if ('caches' in window) {
          caches.keys().then(function (keys) {
            return Promise.all(keys.map(function (k) { return caches.delete(k); }));
          }).then(navigate).catch(navigate);
        } else {
          navigate();
        }
      }
      function maybeReload(reason) {
        if (!reason || !/loading chunk|chunkloaderror|failed to fetch dynamically imported module/i.test(String(reason))) {
          return;
        }
        try {
          if (sessionStorage.getItem(key)) return;
          sessionStorage.setItem(key, '1');
        } catch (e) {}
        hardReload();
      }
      window.addEventListener('unhandledrejection', function (event) {
        var reason = event.reason && (event.reason.message || event.reason);
        maybeReload(reason);
      });
      window.addEventListener('error', function (event) {
        maybeReload(event.message);
      });
      window.addEventListener('load', function () {
        try { sessionStorage.removeItem(key); } catch (e) {}
      });
    })();
  `;

  return (
    <html lang="ru" className={inter.variable}>
      <head>
        <link
          rel="preload"
          href="/brand/logo.png"
          as="image"
          type="image/png"
          fetchPriority="high"
        />
        <Script
          id="stale-chunk-reload-guard"
          strategy="beforeInteractive"
          dangerouslySetInnerHTML={{ __html: staleChunkReloadGuard }}
        />
      </head>
      <body className="font-body antialiased">
        <QueryProvider initialExchangeRates={initialExchangeRates}>
          <CurrencyProvider>
            {children}
          </CurrencyProvider>
          <Toaster />
          <CookieBanner />
          <DeployVersionGuard />
        </QueryProvider>

        <AnalyticsScripts />
        <noscript>
          <div>
            {/* eslint-disable-next-line @next/next/no-img-element -- Metrika noscript pixel */}
            <img
                src={`https://mc.yandex.ru/watch/${YANDEX_METRIKA_COUNTER_ID}`}
                style={{ position: "absolute", left: "-9999px" }}
                alt=""
            />
          </div>
        </noscript>
      </body>
    </html>
  );
}
