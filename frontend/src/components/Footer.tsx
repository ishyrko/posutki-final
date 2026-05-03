"use client";

import Link from "next/link";
import {
  HEADER_REGION_MINSK_SLUG,
  withRegionalCatalogHref,
} from "@/lib/region-header";
import { useHeaderRegionSlug } from "@/hooks/useHeaderRegionSlug";

const Footer = () => {
  const regionSlug = useHeaderRegionSlug();
  const homeHref =
    regionSlug === HEADER_REGION_MINSK_SLUG
      ? "/"
      : withRegionalCatalogHref("/kvartiry/", regionSlug);

  const kvartiry = withRegionalCatalogHref("/kvartiry/", regionSlug);
  const doma = withRegionalCatalogHref("/doma/", regionSlug);

  return (
    <footer className="bg-foreground py-12 md:py-16">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-8 mb-10">
          <div className="col-span-2 md:col-span-1">
            <Link href={homeHref} className="font-display text-xl font-bold text-primary-foreground">
              posutki.by
            </Link>
            <p className="mt-3 text-sm text-primary-foreground/60 leading-relaxed">
              Крупнейший сервис посуточной аренды жилья в Беларуси
            </p>
          </div>

          <div>
            <h4 className="font-display font-semibold text-primary-foreground mb-4 text-sm">Арендаторам</h4>
            <ul className="space-y-2.5">
              {[
                { label: "Квартиры посуточно", href: kvartiry },
                { label: "Дома и коттеджи", href: doma },
                { label: "Статьи и гиды", href: "/stati/" },
              ].map((item) => (
                <li key={item.label}>
                  <Link
                    href={item.href}
                    className="text-sm text-primary-foreground/60 hover:text-primary-foreground transition-colors duration-150"
                  >
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="font-display font-semibold text-primary-foreground mb-4 text-sm">Владельцам</h4>
            <ul className="space-y-2.5">
              {[
                { label: "Разместить жильё", href: "/razmestit/" },
                { label: "Личный кабинет", href: "/kabinet/" },
              ].map((item) => (
                <li key={item.label}>
                  <Link
                    href={item.href}
                    className="text-sm text-primary-foreground/60 hover:text-primary-foreground transition-colors duration-150"
                  >
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="font-display font-semibold text-primary-foreground mb-4 text-sm">Компания</h4>
            <ul className="space-y-2.5">
              {[
                { label: "О нас", href: "/o-nas/" },
                { label: "Контакты", href: "/kontakty" },
                { label: "Блог", href: "/stati/" },
              ].map((item) => (
                <li key={item.label}>
                  <Link
                    href={item.href}
                    className="text-sm text-primary-foreground/60 hover:text-primary-foreground transition-colors duration-150"
                  >
                    {item.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="border-t border-primary-foreground/10 pt-6 flex flex-col md:flex-row items-center justify-between gap-4">
          <p className="text-sm text-primary-foreground/40">
            © {new Date().getFullYear()} posutki.by. Все права защищены.
          </p>
          <div className="flex gap-6 flex-wrap justify-center">
            <Link href="/politika-konfidentsialnosti" className="text-sm text-primary-foreground/40 hover:text-primary-foreground/60 transition-colors">
              Политика конфиденциальности
            </Link>
            <Link href="/kontakty" className="text-sm text-primary-foreground/40 hover:text-primary-foreground/60 transition-colors">
              Контакты
            </Link>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
