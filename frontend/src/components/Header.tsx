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
  Sparkles,
  Star,
  Clock,
  ChevronDown,
  Plus,
  Search,
  BedDouble,
  Users,
  Wifi,
  Car,
  TreePine,
  Flame,
  Bath,
  LayoutDashboard,
} from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { isAuthenticated } from "@/lib/auth";
import { withRegionalCatalogHref } from "@/lib/region-header";
import { useHeaderRegionSlug } from "@/hooks/useHeaderRegionSlug";
import { useSyncExternalStore } from "react";

interface MegaMenuSection {
  title: string;
  items: { label: string; desc: string; icon: React.ReactNode; href: string }[];
}

function buildMegaMenu(regionSlug: string): Record<string, MegaMenuSection[]> {
  const r = (path: string) => withRegionalCatalogHref(path, regionSlug);

  return {
    Квартиры: [
      {
        title: "По количеству комнат",
        items: [
          { label: "Однокомнатные", desc: "Уютные студии и 1-комнатные", icon: <BedDouble className="h-4 w-4" />, href: r("/kvartiry/?rooms=1") },
          { label: "Двухкомнатные", desc: "Просторные квартиры для семей", icon: <Home className="h-4 w-4" />, href: r("/kvartiry/?rooms=2") },
          { label: "Трёхкомнатные+", desc: "Большие апартаменты", icon: <Building2 className="h-4 w-4" />, href: r("/kvartiry/?rooms=3") },
        ],
      },
      {
        title: "По типу",
        items: [
          { label: "Все квартиры", desc: "Комфортное жильё на сутки", icon: <Home className="h-4 w-4" />, href: r("/kvartiry/") },
          { label: "Премиум", desc: "Элитные апартаменты", icon: <Sparkles className="h-4 w-4" />, href: r("/kvartiry/") },
          { label: "С джакузи", desc: "Романтический отдых", icon: <Star className="h-4 w-4" />, href: r("/kvartiry/") },
        ],
      },
      {
        title: "Удобства",
        items: [
          { label: "С Wi‑Fi", desc: "Для работы и отдыха", icon: <Wifi className="h-4 w-4" />, href: r("/kvartiry/") },
          { label: "С парковкой", desc: "Парковка у дома", icon: <Car className="h-4 w-4" />, href: r("/kvartiry/") },
          { label: "Для компаний", desc: "От 4+ гостей", icon: <Users className="h-4 w-4" />, href: r("/kvartiry/") },
        ],
      },
    ],
    Дома: [
      {
        title: "По типу жилья",
        items: [
          { label: "Коттеджи", desc: "Отдельные дома с участком", icon: <Home className="h-4 w-4" />, href: r("/doma/") },
          { label: "Дачи", desc: "Загородный отдых", icon: <TreePine className="h-4 w-4" />, href: r("/dachi/") },
          { label: "Все дома", desc: "Просторные дома", icon: <Building2 className="h-4 w-4" />, href: r("/doma/") },
        ],
      },
      {
        title: "Особенности",
        items: [
          { label: "С баней/сауной", desc: "Отдых с парилкой", icon: <Flame className="h-4 w-4" />, href: r("/doma/") },
          { label: "С бассейном", desc: "Дома с бассейном", icon: <Bath className="h-4 w-4" />, href: r("/doma/") },
          { label: "У воды", desc: "На берегу водоёма", icon: <MapPin className="h-4 w-4" />, href: r("/doma/") },
        ],
      },
      {
        title: "Для кого",
        items: [
          { label: "Для большой компании", desc: "От 8+ гостей", icon: <Users className="h-4 w-4" />, href: r("/doma/") },
          { label: "Для семьи", desc: "Семейный отдых", icon: <Heart className="h-4 w-4" />, href: r("/doma/") },
          { label: "Романтика", desc: "Уединённые домики", icon: <Star className="h-4 w-4" />, href: r("/doma/") },
        ],
      },
    ],
    Города: [
      {
        title: "Областные центры",
        items: [
          { label: "Минск", desc: "Столица", icon: <MapPin className="h-4 w-4" />, href: "/" },
          { label: "Гродно", desc: "Город-музей", icon: <MapPin className="h-4 w-4" />, href: "/grodno/" },
          { label: "Брест", desc: "Запад страны", icon: <MapPin className="h-4 w-4" />, href: "/brest/" },
        ],
      },
      {
        title: "Ещё города",
        items: [
          { label: "Витебск", desc: "Север", icon: <MapPin className="h-4 w-4" />, href: "/vitebsk/" },
          { label: "Гомель", desc: "Юг Беларуси", icon: <MapPin className="h-4 w-4" />, href: "/gomel/" },
          { label: "Могилёв", desc: "Восток", icon: <MapPin className="h-4 w-4" />, href: "/mogilev/" },
        ],
      },
    ],
  };
}

const Header = () => {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [activeMega, setActiveMega] = useState<string | null>(null);
  const [mobileExpanded, setMobileExpanded] = useState<string | null>(null);
  const megaRef = useRef<HTMLDivElement>(null);
  const timeoutRef = useRef<ReturnType<typeof setTimeout>>();

  const isMounted = useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );
  const loggedIn = isMounted ? isAuthenticated() : false;
  const regionSlug = useHeaderRegionSlug();
  const megaMenuData = buildMegaMenu(regionSlug);

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
        <Link href="/" className="font-display text-xl font-bold tracking-tight text-primary">
          posutki.by
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

          <Link
            href={searchHref}
            className="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-colors duration-150"
          >
            <Search className="h-3.5 w-3.5" />
            Поиск
          </Link>

          <Link
            href={`${searchHref}?sort=new`}
            className="flex items-center gap-1 px-3 py-2 rounded-lg text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted/50 transition-colors duration-150"
          >
            <Clock className="h-3.5 w-3.5" />
            Новинки
          </Link>
        </nav>

        <div className="hidden md:flex items-center gap-2">
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
          <Button size="sm" className="gap-2 font-semibold" asChild>
            <Link href="/razmestit/">
              <Plus className="h-4 w-4" />
              Разместить
            </Link>
          </Button>
        </div>

        <Button
          variant="ghost"
          size="icon"
          className="md:hidden text-foreground"
          onClick={() => setMobileOpen(!mobileOpen)}
          aria-label={mobileOpen ? "Закрыть меню" : "Открыть меню"}
        >
          {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
        </Button>
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
              {megaMenuData[activeMega].map((section) => (
                <div key={section.title}>
                  <h3 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">
                    {section.title}
                  </h3>
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
                    {sections.map((section) => (
                      <div key={section.title}>
                        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground px-2 mb-2">
                          {section.title}
                        </p>
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

            <Link
              href={searchHref}
              onClick={() => setMobileOpen(false)}
              className="flex items-center gap-3 py-3 px-2 text-sm font-semibold text-foreground rounded-lg hover:bg-muted/50"
            >
              <Search className="h-4 w-4 text-primary" />
              Поиск
            </Link>
          </div>

          <div className="p-4 border-t border-border space-y-2">
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
            <Link href="/razmestit/" onClick={() => setMobileOpen(false)}>
              <Button size="sm" className="w-full gap-2 justify-center">
                <Plus className="h-4 w-4" />
                Разместить объявление
              </Button>
            </Link>
          </div>
        </div>
      )}
    </header>
  );
};

export default Header;
