"use client";

import { useState, useRef, useEffect, useCallback } from "react";
import {
  Heart,
  Menu,
  User,
  X,
  Home,
  Building2,
  MapPin,
  Star,
  ChevronDown,
  Search,
  BedDouble,
  Users,
  Wifi,
  Car,
  Flame,
  Bath,
  LayoutDashboard,
} from "lucide-react";
import Link from "next/link";
import { ListingSubmitLink } from "@/components/ListingSubmitLink";
import Image from "next/image";
import { Button } from "@/components/ui/button";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { isAuthenticated } from "@/lib/auth";
import { withRegionalCatalogHref } from "@/lib/region-header";
import { useHeaderRegionSlug } from "@/hooks/useHeaderRegionSlug";
import { useHeaderCatalogPropertyType } from "@/hooks/useHeaderCatalogPropertyType";
import { buildCatalogUrl } from "@/features/catalog/slugs";
import { useSyncExternalStore } from "react";
import { useCurrency } from "@/context/CurrencyContext";
import { BynCurrencyMark } from "@/components/BynCurrency";
import type { Currency } from "@/features/properties/types";
import { cn } from "@/lib/utils";

interface MegaMenuSection {
  /** Пустой или отсутствует — колонка без подзаголовка (вторая колонка «Города»). */
  title?: string;
  items: { label: string; desc: string; icon: React.ReactNode; href: string }[];
}

function buildMegaMenu(
  regionSlug: string,
  catalogPropertyType: "apartment" | "house",
): Record<string, MegaMenuSection[]> {
  const r = (path: string) => withRegionalCatalogHref(path, regionSlug);
  const cityHref = (region?: string) =>
    buildCatalogUrl({ region, propertyType: catalogPropertyType });

  return {
    Квартиры: [
      {
        title: "По количеству комнат",
        items: [
          { label: "Однокомнатные", desc: "Уютные студии и 1-комнатные", icon: <BedDouble className="h-4 w-4" />, href: r("/kvartiry/?rooms=1") },
          { label: "Двухкомнатные", desc: "Просторные квартиры для семей", icon: <Home className="h-4 w-4" />, href: r("/kvartiry/?rooms=2") },
          { label: "Трёхкомнатные+", desc: "Большие апартаменты", icon: <Building2 className="h-4 w-4" />, href: r("/kvartiry/?rooms=3%2B") },
        ],
      },
      {
        title: "По типу",
        items: [
          { label: "Все квартиры", desc: "Комфортное жильё на сутки", icon: <Home className="h-4 w-4" />, href: r("/kvartiry/") },
          { label: "С джакузи", desc: "Романтический отдых", icon: <Star className="h-4 w-4" />, href: r("/kvartiry/?amenity=jacuzzi") },
        ],
      },
      {
        title: "Удобства",
        items: [
          { label: "С Wi‑Fi", desc: "Для работы и отдыха", icon: <Wifi className="h-4 w-4" />, href: r("/kvartiry/?amenity=wifi") },
          { label: "С парковкой", desc: "Парковка у дома", icon: <Car className="h-4 w-4" />, href: r("/kvartiry/?amenity=parking") },
          { label: "Для компаний", desc: "От 4+ гостей", icon: <Users className="h-4 w-4" />, href: r("/kvartiry/?guests=4") },
        ],
      },
    ],
    Дома: [
      {
        title: "Каталог",
        items: [
          { label: "Все дома", desc: "Дома и коттеджи на сутки", icon: <Building2 className="h-4 w-4" />, href: r("/doma/") },
        ],
      },
      {
        title: "Особенности",
        items: [
          { label: "С баней/сауной", desc: "Отдых с парилкой", icon: <Flame className="h-4 w-4" />, href: r("/doma/?amenity=sauna") },
          { label: "С бассейном", desc: "Бассейн на участке", icon: <Bath className="h-4 w-4" />, href: r("/doma/?amenity=pool") },
        ],
      },
      {
        title: "Для кого",
        items: [
          { label: "Для большой компании", desc: "От 8+ гостей", icon: <Users className="h-4 w-4" />, href: r("/doma/?guests=8") },
        ],
      },
    ],
    Города: [
      {
        title: "Областные центры",
        items: [
          { label: "Минск", desc: "Столица", icon: <MapPin className="h-4 w-4" />, href: cityHref() },
          { label: "Гродно", desc: "Город-музей", icon: <MapPin className="h-4 w-4" />, href: cityHref("grodno") },
          { label: "Брест", desc: "Юго-запад", icon: <MapPin className="h-4 w-4" />, href: cityHref("brest") },
        ],
      },
      {
        items: [
          { label: "Витебск", desc: "Север", icon: <MapPin className="h-4 w-4" />, href: cityHref("vitebsk") },
          { label: "Гомель", desc: "Юго-восток", icon: <MapPin className="h-4 w-4" />, href: cityHref("gomel") },
          { label: "Могилёв", desc: "Восток", icon: <MapPin className="h-4 w-4" />, href: cityHref("mogilev") },
        ],
      },
    ],
  };
}

function HeaderCurrencyStrip({ variant }: { variant: "desktopToolbar" | "mobileToolbar" | "drawer" }) {
  const { selectedCurrency, setSelectedCurrency } = useCurrency();
  const currencyOptions: { value: Currency; label: React.ReactNode; ariaLabel: string }[] = [
    { value: "BYN", label: <BynCurrencyMark variant="select" />, ariaLabel: "Белорусский рубль" },
    { value: "USD", label: "$", ariaLabel: "Доллар США" },
    { value: "RUB", label: "₽", ariaLabel: "Российский рубль" },
  ];

  const isDrawer = variant === "drawer";

  return (
    <TooltipProvider delayDuration={400}>
      <div
        className={cn(
          "flex items-center rounded-xl border border-border bg-muted/40 p-1 gap-0.5",
          variant === "desktopToolbar" && "mr-1",
          variant === "mobileToolbar" && "shrink-0",
          isDrawer && "mb-3",
        )}
      >
        {currencyOptions.map((opt) => (
          <Tooltip key={opt.value}>
            <TooltipTrigger asChild>
              <button
                type="button"
                aria-label={opt.ariaLabel}
                onClick={() => setSelectedCurrency(opt.value)}
                className={cn(
                  "cursor-pointer rounded-lg text-sm font-semibold transition-all duration-150",
                  isDrawer ? "flex-1 h-10" : "min-w-[2.5rem] h-8 px-2.5",
                  selectedCurrency === opt.value
                    ? cn(
                        "bg-primary text-primary-foreground shadow-md",
                        isDrawer ? "scale-[1.03]" : "scale-[1.04]",
                      )
                    : "text-muted-foreground/60 hover:text-muted-foreground",
                )}
              >
                {opt.label}
              </button>
            </TooltipTrigger>
            <TooltipContent side="bottom">{opt.ariaLabel}</TooltipContent>
          </Tooltip>
        ))}
      </div>
    </TooltipProvider>
  );
}

const Header = () => {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [activeMega, setActiveMega] = useState<string | null>(null);
  const [mobileExpanded, setMobileExpanded] = useState<string | null>(null);
  const megaRef = useRef<HTMLDivElement>(null);
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

  const isMounted = useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );
  const loggedIn = isMounted ? isAuthenticated() : false;
  const regionSlug = useHeaderRegionSlug();
  const catalogPropertyType = useHeaderCatalogPropertyType();
  const megaMenuData = buildMegaMenu(regionSlug, catalogPropertyType);

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (megaRef.current && !megaRef.current.contains(e.target as Node)) {
        setActiveMega(null);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleMouseEnter = useCallback((key: string) => {
    if (timeoutRef.current) clearTimeout(timeoutRef.current);
    setActiveMega(key);
  }, []);

  const handleMouseLeave = useCallback(() => {
    timeoutRef.current = setTimeout(() => setActiveMega(null), 150);
  }, []);

  const searchHref = withRegionalCatalogHref("/kvartiry/", regionSlug);

  return (
    <header ref={megaRef} className="sticky top-0 z-50 bg-card/80 backdrop-blur-xl border-b border-border relative">
      <div className="container mx-auto px-4 flex items-center justify-between h-16">
        <Link href="/" className="flex items-center gap-2">
          <Image
            src="/brand/logo.png"
            alt="posutki.by"
            width={180}
            height={40}
            sizes="(max-width: 640px) 140px, (max-width: 1024px) 160px, 180px"
            priority
            className="w-36 sm:w-40 lg:w-[180px] h-auto"
          />
          <span className="sr-only">posutki.by</span>
        </Link>

        <nav className="hidden md:flex items-center gap-1">
          {Object.keys(megaMenuData).map((key) => (
            <div
              key={key}
              className="relative"
              onMouseEnter={() => handleMouseEnter(key)}
              onMouseLeave={handleMouseLeave}
            >
              <button
                type="button"
                className={`flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors duration-150 ${
                  activeMega === key
                    ? "text-primary bg-primary/5"
                    : "text-muted-foreground hover:text-foreground hover:bg-muted/50"
                }`}
              >
                {key}
                <ChevronDown className={`h-3.5 w-3.5 transition-transform duration-200 ${activeMega === key ? "rotate-180" : ""}`} />
              </button>
            </div>
          ))}
        </nav>

        <div className="hidden md:flex items-center gap-2">
          <HeaderCurrencyStrip variant="desktopToolbar" />
          <Link href="/kabinet/izbrannoe/">
            <Button variant="ghost" size="icon" className="text-muted-foreground hover:text-foreground">
              <Heart className="h-5 w-5" />
            </Button>
          </Link>
          {loggedIn ? (
            <Button variant="ghost" size="sm" className="gap-2 font-medium text-muted-foreground hover:text-foreground" asChild>
              <Link href="/kabinet/">
                <LayoutDashboard className="h-4 w-4" />
                Кабинет
              </Link>
            </Button>
          ) : (
            <Button variant="ghost" size="sm" className="gap-2 font-medium text-muted-foreground hover:text-foreground" asChild>
              <Link href="/login/">
                <User className="h-4 w-4" />
                Войти
              </Link>
            </Button>
          )}
          <Button size="sm" className="font-semibold" asChild>
            <ListingSubmitLink>Сдать жилье</ListingSubmitLink>
          </Button>
        </div>

        <div className="md:hidden flex items-center gap-2">
          <div className="hidden min-[580px]:flex shrink-0">
            <HeaderCurrencyStrip variant="mobileToolbar" />
          </div>
          <Button size="sm" className="font-semibold" asChild>
            <ListingSubmitLink>Сдать жилье</ListingSubmitLink>
          </Button>

          <Button
            variant="ghost"
            size="icon"
            className="text-foreground"
            onClick={() => setMobileOpen(!mobileOpen)}
            aria-label={mobileOpen ? "Закрыть меню" : "Открыть меню"}
          >
            {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </Button>
        </div>
      </div>

      {activeMega && megaMenuData[activeMega] && (
        <div
          className="hidden md:block absolute left-0 right-0 top-full z-50 border-b border-border bg-card shadow-elevated animate-fade-in"
          onMouseEnter={() => {
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
          }}
          onMouseLeave={handleMouseLeave}
        >
          <div className="container mx-auto px-4 py-6">
            <div className="grid grid-cols-3 gap-8">
              {megaMenuData[activeMega].map((section, sectionIndex) => (
                <div key={section.title ?? `section-${sectionIndex}`}>
                  {section.title ? (
                    <h3 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">
                      {section.title}
                    </h3>
                  ) : (
                    <div className="mb-3 h-4" aria-hidden />
                  )}
                  <div className="space-y-1">
                    {section.items.map((item) => (
                      <Link
                        key={item.label}
                        href={item.href}
                        onClick={() => setActiveMega(null)}
                        className="flex items-start gap-3 p-3 rounded-xl hover:bg-muted/50 transition-colors group"
                      >
                        <div className="p-2 rounded-lg bg-primary/10 text-primary shrink-0 group-hover:bg-primary/15 transition-colors">
                          {item.icon}
                        </div>
                        <div>
                          <div className="text-sm font-medium text-foreground">{item.label}</div>
                          <div className="text-xs text-muted-foreground leading-relaxed">{item.desc}</div>
                        </div>
                      </Link>
                    ))}
                  </div>
                </div>
              ))}
            </div>

            <div className="mt-6 pt-5 border-t border-border flex items-center justify-between gap-4">
              <p className="text-sm text-muted-foreground">
                Аренда квартир и домов посуточно по всей Беларуси — проверенные объявления с фото
              </p>
              <Link href={searchHref} onClick={() => setActiveMega(null)}>
                <Button variant="outline" size="sm" className="gap-2 shrink-0">
                  <Search className="h-3.5 w-3.5" />
                  Смотреть все
                </Button>
              </Link>
            </div>
          </div>
        </div>
      )}

      {mobileOpen && (
        <div className="md:hidden border-t border-border bg-card animate-fade-in max-h-[80vh] overflow-y-auto">
          <div className="p-4 space-y-1">
            {Object.entries(megaMenuData).map(([key, sections]) => (
              <div key={key}>
                <button
                  type="button"
                  onClick={() => setMobileExpanded(mobileExpanded === key ? null : key)}
                  className="w-full flex items-center justify-between py-3 px-2 text-sm font-semibold text-foreground rounded-lg hover:bg-muted/50 transition-colors"
                >
                  {key}
                  <ChevronDown className={`h-4 w-4 text-muted-foreground transition-transform ${mobileExpanded === key ? "rotate-180" : ""}`} />
                </button>

                {mobileExpanded === key && (
                  <div className="pb-2 pl-2 space-y-4 animate-fade-in">
                    {sections.map((section, sectionIndex) => (
                      <div key={section.title ?? `section-${sectionIndex}`}>
                        {section.title ? (
                          <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground px-2 mb-2">
                            {section.title}
                          </p>
                        ) : null}
                        {section.items.map((item) => (
                          <Link
                            key={item.label}
                            href={item.href}
                            onClick={() => setMobileOpen(false)}
                            className="flex items-center gap-3 py-2.5 px-2 rounded-lg hover:bg-muted/50 transition-colors"
                          >
                            <span className="text-primary">{item.icon}</span>
                            <span className="text-sm font-medium text-foreground">{item.label}</span>
                          </Link>
                        ))}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>

          <div className="p-4 border-t border-border space-y-2">
            <div className="min-[580px]:hidden">
              <HeaderCurrencyStrip variant="drawer" />
            </div>
            <Link href="/kabinet/izbrannoe/" onClick={() => setMobileOpen(false)}>
              <Button variant="outline" size="sm" className="w-full gap-2 justify-center mb-2">
                <Heart className="h-4 w-4" />
                Избранное
              </Button>
            </Link>
            {loggedIn ? (
              <Link href="/kabinet/" onClick={() => setMobileOpen(false)}>
                <Button variant="outline" size="sm" className="w-full gap-2 justify-center mb-2">
                  <LayoutDashboard className="h-4 w-4" />
                  Кабинет
                </Button>
              </Link>
            ) : (
              <Link href="/login/" onClick={() => setMobileOpen(false)}>
                <Button variant="outline" size="sm" className="w-full gap-2 justify-center mb-2">
                  <User className="h-4 w-4" />
                  Войти
                </Button>
              </Link>
            )}
            <ListingSubmitLink onClick={() => setMobileOpen(false)}>
              <Button size="sm" className="w-full justify-center">
                Сдать жилье
              </Button>
            </ListingSubmitLink>
          </div>
        </div>
      )}
    </header>
  );
};

export default Header;
