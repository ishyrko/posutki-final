"use client";

import Link from "next/link";
import { ListingSubmitLink } from "@/components/ListingSubmitLink";
import {
  HEADER_REGION_MINSK_SLUG,
  withRegionalCatalogHref,
} from "@/lib/region-header";
import { useHeaderRegionSlug } from "@/hooks/useHeaderRegionSlug";
import { COMPANY } from "@/lib/company";
import Image from "next/image";

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
            <Link href={homeHref} className="inline-flex items-center">
              <Image
                src="/brand/logo.png"
                alt="posutki.by"
                width={0}
                height={0}
                sizes="(max-width: 640px) 140px, (max-width: 1024px) 160px, 180px"
                className="h-auto w-36 sm:w-40 lg:w-[180px] brightness-0 invert"
              />
              <span className="sr-only">posutki.by</span>
            </Link>
            <p className="mt-3 text-sm text-primary-foreground/60 leading-relaxed">
              Сервис посуточной аренды жилья в Беларуси
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
                { label: "Разместить жильё бесплатно", link: "listing" as const },
                { label: "Тарифы", href: "/tarify/" },
                { label: "Оплата", href: "/oplata/" },
                { label: "Интеграция с RealtyCalendar", href: "/integratsiya-s-realty-calendar/" },
                { label: "Личный кабинет", href: "/kabinet/" },
              ].map((item) => (
                <li key={item.label}>
                  {item.link === "listing" ? (
                    <ListingSubmitLink className="text-sm text-primary-foreground/60 hover:text-primary-foreground transition-colors duration-150">
                      {item.label}
                    </ListingSubmitLink>
                  ) : (
                    <Link
                      href={item.href}
                      className="text-sm text-primary-foreground/60 hover:text-primary-foreground transition-colors duration-150"
                    >
                      {item.label}
                    </Link>
                  )}
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

        <div className="border-t border-primary-foreground/10 pt-8 space-y-6">
          <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div className="max-w-2xl space-y-1.5 text-sm leading-relaxed text-primary-foreground/50">
              <p className="font-medium text-primary-foreground/70">{COMPANY.legalName}</p>
              <p>
                Дата гос. регистрации: {COMPANY.registeredAt}. УНП {COMPANY.unp}.{" "}
                {COMPANY.registrationAuthority}
              </p>
              <p>{COMPANY.address}</p>
              <p>
                {COMPANY.workingHours}.{" "}
                <a
                  href={`mailto:${COMPANY.email}`}
                  className="hover:text-primary-foreground/80 transition-colors"
                >
                  {COMPANY.email}
                </a>
                {", "}
                <a
                  href={`tel:${COMPANY.phone}`}
                  className="hover:text-primary-foreground/80 transition-colors"
                >
                  {COMPANY.phoneDisplay}
                </a>
              </p>
            </div>

            <div className="shrink-0">
              <Image
                src="/brand/payment-systems.svg"
                alt="Платёжные системы: Visa, Mastercard, Белкарт, bePaid, Google Pay"
                width={927}
                height={100}
                className="h-11 w-auto max-w-full sm:h-14"
                unoptimized
              />
            </div>
          </div>

          <div className="flex flex-col md:flex-row items-center justify-between gap-4 border-t border-primary-foreground/10 pt-6">
            <p className="text-sm text-primary-foreground/40">
              © {new Date().getFullYear()} posutki.by. Все права защищены.
            </p>
            <div className="flex gap-6 flex-wrap justify-center">
              <Link href="/politika-konfidentsialnosti" className="text-sm text-primary-foreground/40 hover:text-primary-foreground/60 transition-colors">
                Политика конфиденциальности
              </Link>
              <Link href="/soglasie-na-obrabotku-personalnyh-dannyh" className="text-sm text-primary-foreground/40 hover:text-primary-foreground/60 transition-colors">
                Согласие на обработку персональных данных
              </Link>
              <Link href="/usloviya-ispolzovaniya" className="text-sm text-primary-foreground/40 hover:text-primary-foreground/60 transition-colors">
                Условия использования
              </Link>
              <Link href="/publichnaya-oferta" className="text-sm text-primary-foreground/40 hover:text-primary-foreground/60 transition-colors">
                Публичная оферта
              </Link>
              <Link href="/kontakty" className="text-sm text-primary-foreground/40 hover:text-primary-foreground/60 transition-colors">
                Контакты
              </Link>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
