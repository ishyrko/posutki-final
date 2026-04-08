"use client";

import { MapPin, Mail } from "lucide-react";
import NextImage from "next/image";
import Link from "next/link";
import {
  HEADER_REGION_MINSK_SLUG,
  withRegionalCatalogHref,
} from "@/lib/region-header";
import { useHeaderRegionSlug } from "@/hooks/useHeaderRegionSlug";

const footerLinks = [
  {
    title: "Недвижимость",
    links: [
      { label: "Купить квартиру", href: "/prodazha/kvartiry/" },
      { label: "Снять квартиру", href: "/arenda/kvartiry/" },
      { label: "Квартиры на сутки", href: "/posutochno/kvartiry/" },
      { label: "Дома и коттеджи", href: "/prodazha/doma/" },
    ],
  },
  {
    title: "Сервисы",
    links: [
      { label: "Ипотечный калькулятор", href: "/kalkulator-ipoteki/" },
      { label: "Статьи и гиды", href: "/stati/" },
    ],
  },
  {
    title: "Компания",
    links: [
      { label: "О нас", href: "/o-nas/" },
      { label: "Контакты", href: "/kontakty" },
      { label: "Условия использования", href: "/usloviya-ispolzovaniya" },
      { label: "Политика конфиденциальности", href: "/politika-konfidentsialnosti" },
    ],
  },
];

const Footer = () => {
  const regionSlug = useHeaderRegionSlug();
  const homeHref =
    regionSlug === HEADER_REGION_MINSK_SLUG ? "/" : `/${regionSlug}/`;

  return (
    <footer className="bg-dark-bg border-t border-dark-card">
      <div className="container mx-auto px-4 py-14">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-10">
          {/* Brand */}
          <div>
            <Link href={homeHref} className="inline-flex items-center mb-4">
              <NextImage
                src="/rnb-logo-transparent.png"
                alt="RNB.by"
                width={600}
                height={207}
                className="h-14 w-auto object-contain"
              />
            </Link>
            <p className="text-sm text-dark-fg/40 mb-5 leading-relaxed">
              Ваш надёжный помощник в поиске недвижимости по всей Беларуси.
            </p>
            <div className="flex flex-col gap-2.5 text-sm text-dark-fg/40">
              <span className="flex items-center gap-2">
                <MapPin className="w-3.5 h-3.5" />
                Минск, Беларусь
              </span>
              <span className="flex items-center gap-2">
                <Mail className="w-3.5 h-3.5" />
                info@rnb.by
              </span>
            </div>
          </div>

          {/* Link Columns */}
          {footerLinks.map((col) => (
            <div key={col.title}>
              <h4 className="text-sm font-semibold text-dark-fg mb-4">{col.title}</h4>
              <ul className="flex flex-col gap-2.5">
                {col.links.map((link) => (
                  <li key={link.label}>
                    <Link
                      href={withRegionalCatalogHref(link.href, regionSlug)}
                      className="text-sm text-dark-fg/40 hover:text-primary transition-colors"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        <div className="mt-12 pt-6 border-t border-dark-card">
          <span className="text-xs text-dark-fg/30">© 2026 RNB.by. Все права защищены.</span>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
