"use client";

import { useState, useRef, useCallback, useMemo, useSyncExternalStore } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Menu, X, Heart, User, ChevronDown, Plus, LayoutDashboard, MapPin, Check } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover";
import Link from "next/link";
import NextImage from "next/image";
import { useRouter } from "next/navigation";
import { isAuthenticated } from "@/lib/auth";
import { withRegionalCatalogHref } from "@/lib/region-header";
import { useHeaderRegionSlug } from "@/hooks/useHeaderRegionSlug";

type City = {
  name: string;
  slug: string;
  href: string;
};

const CITIES: readonly City[] = [
  { name: "Минск", slug: "minsk", href: "/" },
  { name: "Брест", slug: "brest", href: "/brest/" },
  { name: "Витебск", slug: "vitebsk", href: "/vitebsk/" },
  { name: "Гомель", slug: "gomel", href: "/gomel/" },
  { name: "Гродно", slug: "grodno", href: "/grodno/" },
  { name: "Могилёв", slug: "mogilev", href: "/mogilev/" },
] as const;

interface MegaMenuItem {
  label: string;
  href: string;
}

interface MegaMenuColumn {
  title: string;
  items: MegaMenuItem[];
}

interface NavItem {
  label: string;
  href: string;
  megaMenu?: MegaMenuColumn[];
  disableTopLevelLink?: boolean;
}

const navItems: NavItem[] = [
  {
    label: "Продажа",
    href: "/prodazha/",
    disableTopLevelLink: true,
    megaMenu: [
      {
        title: "Жилая",
        items: [
          { label: "Квартиры", href: "/prodazha/kvartiry/" },
          { label: "Комнаты", href: "/prodazha/komnaty/" },
        ],
      },
      {
        title: "Загородная",
        items: [
          { label: "Коттеджи, дома", href: "/prodazha/doma/" },
          { label: "Дачи", href: "/prodazha/dachi/" },
          { label: "Участки", href: "/prodazha/uchastki/" },
        ],
      },
      {
        title: "Для авто",
        items: [
          { label: "Гаражи", href: "/prodazha/garazhi/" },
          { label: "Машиноместа", href: "/prodazha/mashinomesta/" },
        ],
      },
    ],
  },
  {
    label: "Аренда",
    href: "/arenda/",
    disableTopLevelLink: true,
    megaMenu: [
      {
        title: "Долгосрочная аренда",
        items: [
          { label: "Квартиры", href: "/arenda/kvartiry/" },
          { label: "Комнаты", href: "/arenda/komnaty/" },
          { label: "Коттеджи, дома", href: "/arenda/doma/" },
          { label: "Дачи", href: "/arenda/dachi/" },
          { label: "Гаражи", href: "/arenda/garazhi/" },
          { label: "Машиноместа", href: "/arenda/mashinomesta/" },
        ],
      },
      {
        title: "Посуточная аренда",
        items: [
          { label: "Квартиры", href: "/posutochno/kvartiry/" },
          { label: "Коттеджи, дома", href: "/posutochno/doma/" },
          { label: "Дачи", href: "/posutochno/dachi/" },
        ],
      },
    ],
  },
  {
    label: "Коммерческая",
    href: "/kommercheskaya/",
    disableTopLevelLink: true,
    megaMenu: [
      {
        title: "Купить",
        items: [
          { label: "Офисы", href: "/prodazha/ofisy/" },
          { label: "Торговые помещения", href: "/prodazha/torgovye/" },
          { label: "Склады", href: "/prodazha/sklady/" },
        ],
      },
      {
        title: "Арендовать",
        items: [
          { label: "Офисы", href: "/arenda/ofisy/" },
          { label: "Торговые помещения", href: "/arenda/torgovye/" },
          { label: "Склады", href: "/arenda/sklady/" },
        ],
      },
    ],
  },
  {
    label: "Статьи",
    href: "/stati/",
    disableTopLevelLink: true,
    megaMenu: [
      {
        title: "Рынок и покупатели",
        items: [
          { label: "Все статьи", href: "/stati/" },
          { label: "Новости рынка", href: "/stati/rynok/" },
          { label: "Гиды для покупателей", href: "/stati/pokupatelyam/" },
          { label: "Советы продавцам", href: "/stati/prodavtsam/" },
        ],
      },
      {
        title: "Инвестиции, право и дом",
        items: [
          { label: "Инвестиции", href: "/stati/investitsii/" },
          { label: "Юридические вопросы", href: "/stati/pravo/" },
          { label: "Обзоры районов", href: "/stati/rayony/" },
          { label: "Ремонт и дизайн", href: "/stati/remont/" },
        ],
      },
    ],
  },
];

const navLinkBase =
  "relative px-3 py-2 text-sm transition-colors rounded-md flex items-center gap-1 cursor-pointer";
const navLinkIdle = `${navLinkBase} text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card`;
const navLinkActive = `${navLinkBase} text-dark-fg`;

const Header = () => {
  const router = useRouter();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [activeMenu, setActiveMenu] = useState<string | null>(null);
  const [mobileExpanded, setMobileExpanded] = useState<string | null>(null);
  const [regionOpen, setRegionOpen] = useState(false);
  const closeTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const isMounted = useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );
  const loggedIn = isMounted ? isAuthenticated() : false;
  const regionSlug = useHeaderRegionSlug();
  const currentCity = useMemo(
    () => CITIES.find((c) => c.slug === regionSlug) ?? CITIES[0],
    [regionSlug],
  );

  const selectCity = useCallback((city: City) => {
    setRegionOpen(false);
    router.push(city.href);
  }, [router]);

  const openMenu = useCallback((label: string) => {
    if (closeTimer.current) {
      clearTimeout(closeTimer.current);
      closeTimer.current = null;
    }
    setActiveMenu(label);
  }, []);

  const scheduleClose = useCallback(() => {
    closeTimer.current = setTimeout(() => setActiveMenu(null), 150);
  }, []);

  const cancelClose = useCallback(() => {
    if (closeTimer.current) {
      clearTimeout(closeTimer.current);
      closeTimer.current = null;
    }
  }, []);

  const withSelectedRegion = useCallback(
    (href: string) => withRegionalCatalogHref(href, regionSlug),
    [regionSlug],
  );

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-dark-bg/80 backdrop-blur-xl border-b border-dark-card">
      <div className="container mx-auto px-4 h-16 flex items-center justify-between">
        {/* Logo */}
        <Link href={currentCity.href} className="flex items-center gap-2">
          <NextImage
            src="/rnb-logo-transparent.png"
            alt="RNB.by"
            width={600}
            height={207}
            priority
            className="h-14 w-auto object-contain"
          />
        </Link>

        {/* Desktop Region Selector */}
        <Popover open={regionOpen} onOpenChange={setRegionOpen}>
          <PopoverTrigger asChild>
            <button className="hidden min-[800px]:flex items-center gap-1.5 px-2.5 py-1.5 text-sm text-dark-fg/70 hover:text-dark-fg transition-colors rounded-md hover:bg-dark-card">
              <MapPin className="w-3.5 h-3.5" />
              {currentCity.name}
              <ChevronDown className={`w-3 h-3 transition-transform ${regionOpen ? "rotate-180" : ""}`} />
            </button>
          </PopoverTrigger>
          <PopoverContent
            align="start"
            sideOffset={8}
            className="w-44 p-1.5 bg-dark-bg/95 backdrop-blur-xl border-dark-card"
          >
            {CITIES.map((city) => (
              <button
                key={city.slug}
                onClick={() => selectCity(city)}
                className="w-full flex items-center justify-between px-2.5 py-2 text-sm rounded-md transition-colors text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card"
              >
                {city.name}
                {city.slug === currentCity.slug && <Check className="w-3.5 h-3.5 text-primary" />}
              </button>
            ))}
          </PopoverContent>
        </Popover>

        {/* Desktop Nav */}
        <nav className="hidden min-[800px]:flex items-center gap-1">
          {navItems.map((item) => {
            const hasMega = !!item.megaMenu;
            const isActive = activeMenu === item.label;
            const itemHref = withSelectedRegion(item.href);
            const isTopLevelClickable = !item.disableTopLevelLink;

            const linkClass = isActive ? navLinkActive : navLinkIdle;

            const underline = isActive && (
              <motion.span
                layoutId="nav-underline"
                className="absolute bottom-0 left-2 right-2 h-0.5 bg-primary rounded-full"
                transition={{ type: "spring", stiffness: 500, damping: 30 }}
              />
            );

            const trigger = item.href.startsWith("/") && !hasMega ? (
              <Link
                href={itemHref}
                className={linkClass}
              >
                {item.label}
                {hasMega && (
                  <ChevronDown className={`w-3.5 h-3.5 transition-transform ${isActive ? "rotate-180" : ""}`} />
                )}
                {underline}
              </Link>
            ) : hasMega && !isTopLevelClickable ? (
              <button
                type="button"
                className={linkClass}
                aria-expanded={isActive}
                aria-haspopup="true"
                onMouseEnter={() => openMenu(item.label)}
                onClick={() => {
                  if (typeof window === "undefined" || !window.matchMedia("(hover: none)").matches) {
                    return;
                  }
                  cancelClose();
                  setActiveMenu((m) => (m === item.label ? null : item.label));
                }}
              >
                {item.label}
                {hasMega && (
                  <ChevronDown className={`w-3.5 h-3.5 transition-transform ${isActive ? "rotate-180" : ""}`} />
                )}
                {underline}
              </button>
            ) : (
              <a
                href={itemHref}
                className={linkClass}
                onMouseEnter={hasMega ? () => openMenu(item.label) : undefined}
              >
                {item.label}
                {hasMega && (
                  <ChevronDown className={`w-3.5 h-3.5 transition-transform ${isActive ? "rotate-180" : ""}`} />
                )}
                {underline}
              </a>
            );

            if (!hasMega) {
              return <div key={item.label}>{trigger}</div>;
            }

            return (
              <div
                key={item.label}
                onMouseEnter={() => openMenu(item.label)}
                onMouseLeave={scheduleClose}
              >
                {trigger}
              </div>
            );
          })}
        </nav>

        {/* Desktop Actions (from 800px; full "Подать объявление" text from 1000px, icon + only between 800–999px) */}
        <div className="hidden min-[800px]:flex items-center gap-2">
          <Link href="/kabinet/izbrannoe/" className="p-2 text-dark-fg/70 hover:text-dark-fg transition-colors rounded-md hover:bg-dark-card">
            <Heart className="w-4 h-4" />
          </Link>
          <Button
            size="icon"
            asChild
            className="h-9 w-9 shrink-0 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0 min-[1000px]:hidden"
          >
            <Link href="/razmestit/" aria-label="Подать объявление" title="Подать объявление">
              <Plus className="w-4 h-4" />
            </Link>
          </Button>
          <Button size="sm" asChild className="hidden min-[1000px]:flex bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0">
            <Link href="/razmestit/">
              <Plus className="w-4 h-4 mr-1.5" />
              Подать объявление
            </Link>
          </Button>
          {loggedIn ? (
            <Button size="sm" variant="ghost" asChild className="text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card border border-dark-card bg-transparent">
              <Link href="/kabinet/">
                <LayoutDashboard className="w-4 h-4 mr-1.5" />
                Кабинет
              </Link>
            </Button>
          ) : (
            <Button size="sm" variant="ghost" asChild className="text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card border border-dark-card bg-transparent">
              <Link href="/login/">
                <User className="w-4 h-4 mr-1.5" />
                Войти
              </Link>
            </Button>
          )}
        </div>

        {/* Mobile: подать объявление (+) и меню (only below 800px) */}
        <div className="flex min-[800px]:hidden items-center gap-2">
          <Button
            size="icon"
            asChild
            className="h-9 w-9 shrink-0 bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity border-0"
          >
            <Link href="/razmestit/" aria-label="Подать объявление" title="Подать объявление">
              <Plus className="w-4 h-4" />
            </Link>
          </Button>
          <button
            type="button"
            className="p-2 text-dark-fg/70"
            onClick={() => setMobileOpen(!mobileOpen)}
            aria-label={mobileOpen ? "Закрыть меню" : "Открыть меню"}
          >
            {mobileOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>
      </div>

      {/* Desktop Mega Menu Panel */}
      <AnimatePresence>
        {activeMenu && (() => {
          const item = navItems.find((n) => n.label === activeMenu);
          if (!item?.megaMenu) return null;
          return (
            <motion.div
              key={activeMenu}
              initial={{ opacity: 0, y: -4 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -4 }}
              transition={{ duration: 0.15 }}
              className="hidden min-[800px]:block absolute left-0 right-0 top-16 bg-dark-bg/95 backdrop-blur-xl border-b border-dark-card z-40"
              onMouseEnter={cancelClose}
              onMouseLeave={scheduleClose}
            >
              <div className="container mx-auto px-4 py-6">
                <div className="flex gap-12 justify-center">
                  {item.megaMenu.map((column) => (
                    <div key={column.title} className="min-w-[200px]">
                      <h4 className="text-sm font-bold uppercase tracking-wider text-dark-fg mb-3">
                        {column.title}
                      </h4>
                      <ul className="flex flex-col gap-1">
                        {column.items.map((subItem) => (
                          <li key={subItem.href}>
                            <Link
                              href={withSelectedRegion(subItem.href)}
                              className="block px-2 py-1.5 text-sm text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card rounded-md transition-colors"
                              onClick={() => setActiveMenu(null)}
                            >
                              {subItem.label}
                            </Link>
                          </li>
                        ))}
                      </ul>
                    </div>
                  ))}
                </div>
              </div>
            </motion.div>
          );
        })()}
      </AnimatePresence>

      {/* Mobile Menu */}
      <AnimatePresence>
        {mobileOpen && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: "auto", opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            className="min-[800px]:hidden bg-dark-bg border-b border-dark-card overflow-hidden"
          >
            <nav className="px-4 py-4 flex flex-col gap-1">
              {navItems.map((item) => {
                if (item.megaMenu) {
                  const isExpanded = mobileExpanded === item.label;
                  return (
                    <div key={item.label}>
                      <button
                        onClick={() => setMobileExpanded(isExpanded ? null : item.label)}
                        className="w-full px-3 py-2.5 text-sm text-dark-fg/70 hover:text-dark-fg rounded-md hover:bg-dark-card flex items-center justify-between"
                      >
                        {item.label}
                        <ChevronDown className={`w-4 h-4 transition-transform ${isExpanded ? "rotate-180" : ""}`} />
                      </button>
                      <AnimatePresence>
                        {isExpanded && (
                          <motion.div
                            initial={{ height: 0, opacity: 0 }}
                            animate={{ height: "auto", opacity: 1 }}
                            exit={{ height: 0, opacity: 0 }}
                            className="overflow-hidden"
                          >
                            <div className="pl-3 pb-1">
                              {item.megaMenu.map((column) => (
                                <div key={column.title} className="mt-2">
                                  <span className="px-3 text-xs font-semibold uppercase tracking-wider text-dark-fg/40">
                                    {column.title}
                                  </span>
                                  {column.items.map((subItem) => (
                                    <Link
                                      key={subItem.href}
                                      href={withSelectedRegion(subItem.href)}
                                      className="block px-3 py-2 text-sm text-dark-fg/70 hover:text-dark-fg rounded-md hover:bg-dark-card"
                                      onClick={() => setMobileOpen(false)}
                                    >
                                      {subItem.label}
                                    </Link>
                                  ))}
                                </div>
                              ))}
                            </div>
                          </motion.div>
                        )}
                      </AnimatePresence>
                    </div>
                  );
                }

                return (
                  <a
                    key={item.label}
                    href={item.href}
                    className="px-3 py-2.5 text-sm text-dark-fg/70 hover:text-dark-fg rounded-md hover:bg-dark-card"
                  >
                    {item.label}
                  </a>
                );
              })}
              <div className="mt-3 pt-3 border-t border-dark-card flex gap-2">
                <Button size="sm" asChild className="flex-1 bg-gradient-primary text-primary-foreground border-0">
                  <Link href="/razmestit/">
                    <Plus className="w-4 h-4 mr-1.5" />
                    Подать объявление
                  </Link>
                </Button>
                {loggedIn ? (
                  <Button size="sm" variant="ghost" asChild className="flex-1 text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card border border-dark-card bg-transparent">
                    <Link href="/kabinet/">
                      <LayoutDashboard className="w-4 h-4 mr-1.5" />
                      Кабинет
                    </Link>
                  </Button>
                ) : (
                  <Button size="sm" variant="ghost" asChild className="flex-1 text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card border border-dark-card bg-transparent">
                    <Link href="/login/">
                      <User className="w-4 h-4 mr-1.5" />
                      Войти
                    </Link>
                  </Button>
                )}
              </div>
              <div className="mt-3 pt-3 border-t border-dark-card px-3">
                <span className="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-dark-fg/40 mb-2">
                  <MapPin className="w-3 h-3" />
                  Город
                </span>
                <div className="flex flex-wrap gap-1.5">
                  {CITIES.map((city) => (
                    <button
                      key={city.slug}
                      onClick={() => {
                        selectCity(city);
                        setMobileOpen(false);
                      }}
                      className={`px-2.5 py-1.5 text-xs rounded-md transition-colors ${
                        city.slug === currentCity.slug
                          ? "bg-primary/15 text-primary font-medium"
                          : "text-dark-fg/70 hover:text-dark-fg hover:bg-dark-card"
                      }`}
                    >
                      {city.name}
                    </button>
                  ))}
                </div>
              </div>
            </nav>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  );
};

export default Header;
