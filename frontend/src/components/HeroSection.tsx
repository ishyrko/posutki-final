"use client";

import { useState, useRef, useEffect, useMemo } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Search, MapPin, Home, ChevronDown, Check, Train, Building2, X } from "lucide-react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { buildCatalogUrl } from "@/features/catalog/slugs";
import NextImage from "next/image";
import type { StaticImageData } from "next/image";
import heroBgMinsk from "@/assets/minsk.webp";
import heroBgBrest from "@/assets/brest.webp";
import heroBgVitebsk from "@/assets/vitebsk.webp";
import heroBgGomel from "@/assets/gomel.webp";
import heroBgGrodno from "@/assets/grodno.webp";
import heroBgMogilev from "@/assets/mogilev.webp";

const HERO_BG_BY_REGION: Record<string, StaticImageData> = {
  brest: heroBgBrest,
  vitebsk: heroBgVitebsk,
  gomel: heroBgGomel,
  grodno: heroBgGrodno,
  mogilev: heroBgMogilev,
};

const tabs = ["Купить", "Снять", "Посуточно"];
const TAB_DEAL_TYPES = ["sale", "rent", "daily"] as const;

type Suggestion = { type: "city" | "metro"; label: string };

const BASE_SUGGESTIONS: Suggestion[] = [
  { type: "city", label: "Минск" },
  { type: "city", label: "Гомель" },
  { type: "city", label: "Брест" },
  { type: "city", label: "Гродно" },
  { type: "city", label: "Витебск" },
  { type: "city", label: "Могилёв" },
];

const MINSK_METRO_SUGGESTION: Suggestion = { type: "metro", label: "Возле метро" };

const CITY_TO_REGION: Record<string, string | undefined> = {
  Минск: undefined,
  Гомель: "gomel",
  Брест: "brest",
  Гродно: "grodno",
  Витебск: "vitebsk",
  Могилёв: "mogilev",
  Могилев: "mogilev",
};

const suggestionIcon = (type: Suggestion["type"]) => {
  switch (type) {
    case "city": return <Building2 className="w-4 h-4 text-primary" />;
    case "metro": return <Train className="w-4 h-4 text-blue-400" />;
  }
};

const typeLabel = (type: Suggestion["type"]) => {
  switch (type) {
    case "city": return "Города";
    case "metro": return "Метро";
  }
};

/** Пусто или ровно один вариант из списка (точное совпадение или единственный по подстроке). */
function resolveLocationSuggestion(
  query: string,
  list: Suggestion[],
): Suggestion | null {
  const trimmed = query.trim();
  if (!trimmed) return null;
  const lower = trimmed.toLowerCase();
  const exact = list.find((s) => s.label.toLowerCase() === lower);
  if (exact) return exact;
  const filtered = list.filter((s) => s.label.toLowerCase().includes(lower));
  if (filtered.length === 1) return filtered[0];
  return null;
}

const propertyTypes = [
  { value: "all", label: "Любой тип" },
  { value: "apartment", label: "Квартира" },
  { value: "house", label: "Дом" },
  { value: "room", label: "Комната" },
  { value: "land", label: "Участок" },
  { value: "garage", label: "Гараж" },
  { value: "parking", label: "Машиноместо" },
  { value: "dacha", label: "Дача" },
  { value: "office", label: "Офис" },
  { value: "retail", label: "Торговое" },
  { value: "warehouse", label: "Склад" },
];

const DAILY_PROPERTY_TYPE_VALUES = new Set(["apartment", "house", "dacha"]);

interface HeroSectionProps {
  regionSlug?: string;
}

const REGION_HERO_CONTENT: Record<
  string,
  { badge: string; titleSuffix: string; description: string }
> = {
  brest: {
    badge: "Брест и область · Недвижимость",
    titleSuffix: "Брест и область",
    description:
      "Квартиры, дома и коммерческая недвижимость в Бресте и Брестской области. Актуальные объявления и удобный поиск по региону.",
  },
  vitebsk: {
    badge: "Витебск и область · Недвижимость",
    titleSuffix: "Витебск и область",
    description:
      "Квартиры, дома и коммерческая недвижимость в Витебске и Витебской области. Актуальные объявления и удобный поиск по региону.",
  },
  gomel: {
    badge: "Гомель и область · Недвижимость",
    titleSuffix: "Гомель и область",
    description:
      "Квартиры, дома и коммерческая недвижимость в Гомеле и Гомельской области. Актуальные объявления и удобный поиск по региону.",
  },
  grodno: {
    badge: "Гродно и область · Недвижимость",
    titleSuffix: "Гродно и область",
    description:
      "Квартиры, дома и коммерческая недвижимость в Гродно и Гродненской области. Актуальные объявления и удобный поиск по региону.",
  },
  mogilev: {
    badge: "Могилевская область · Недвижимость",
    titleSuffix: "Могилев и область",
    description:
      "Квартиры, дома и коммерческая недвижимость в Могилеве и Могилевской области. Актуальные объявления и удобный поиск по региону.",
  },
};

const defaultHeroContent = {
  badge: "Беларусь · Недвижимость",
  titleSuffix: "Минск и область",
  description:
    "Тысячи объектов недвижимости по всей Беларуси. Покупка, продажа и аренда — быстро, удобно и безопасно.",
};

const HeroSection = ({ regionSlug }: HeroSectionProps) => {
  const router = useRouter();
  const [activeTab, setActiveTab] = useState(0);
  const [selectedType, setSelectedType] = useState("all");
  const [typeOpen, setTypeOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  const [searchQuery, setSearchQuery] = useState("");
  const [searchFocused, setSearchFocused] = useState(false);
  const searchRef = useRef<HTMLDivElement>(null);

  const dealType = TAB_DEAL_TYPES[activeTab] ?? "sale";
  const availablePropertyTypes = useMemo(() => {
    let types = propertyTypes;
    if (dealType === "daily") {
      types = types.filter(
        (type) => type.value === "all" || DAILY_PROPERTY_TYPE_VALUES.has(type.value)
      );
    } else if (dealType === "rent") {
      types = types.filter((type) => type.value === "all" || type.value !== "land");
    }
    return types;
  }, [dealType]);
  const effectiveSelectedType = availablePropertyTypes.some((type) => type.value === selectedType)
    ? selectedType
    : "all";
  const selectedLabel =
    availablePropertyTypes.find((t) => t.value === effectiveSelectedType)?.label ?? "Тип жилья";
  const suggestions = useMemo(
    () => (regionSlug ? BASE_SUGGESTIONS : [MINSK_METRO_SUGGESTION, ...BASE_SUGGESTIONS]),
    [regionSlug]
  );

  const filtered = useMemo(() => {
    if (!searchQuery.trim()) return suggestions;
    const q = searchQuery.toLowerCase();
    return suggestions.filter((s) => s.label.toLowerCase().includes(q));
  }, [searchQuery, suggestions]);

  const grouped = useMemo(() => {
    const map = new Map<Suggestion["type"], Suggestion[]>();
    for (const s of filtered) {
      if (!map.has(s.type)) map.set(s.type, []);
      map.get(s.type)!.push(s);
    }
    return map;
  }, [filtered]);

  const showAutocomplete = searchFocused && filtered.length > 0;
  const heroContent = regionSlug
    ? (REGION_HERO_CONTENT[regionSlug] ?? defaultHeroContent)
    : defaultHeroContent;

  const heroBg = useMemo(
    () =>
      regionSlug && HERO_BG_BY_REGION[regionSlug]
        ? HERO_BG_BY_REGION[regionSlug]
        : heroBgMinsk,
    [regionSlug]
  );

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) {
        setTypeOpen(false);
      }
      if (searchRef.current && !searchRef.current.contains(e.target as Node)) {
        setSearchFocused(false);
      }
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  /** Синхронизирует поле с вариантом из списка или очищает; возвращает итоговый выбор для навигации. */
  const resolveAndSyncLocation = (): Suggestion | null => {
    const resolved = resolveLocationSuggestion(searchQuery, suggestions);
    if (!searchQuery.trim()) {
      return null;
    }
    if (resolved) {
      setSearchQuery(resolved.label);
      return resolved;
    }
    setSearchQuery("");
    return null;
  };

  const handleSearch = () => {
    const resolved = resolveAndSyncLocation();

    const propertyType = effectiveSelectedType !== "all" ? effectiveSelectedType : undefined;
    let region = regionSlug;
    let nearMetro = false;

    if (resolved?.type === "city") {
      region = CITY_TO_REGION[resolved.label];
    } else if (resolved?.type === "metro" && !regionSlug) {
      nearMetro = true;
      region = undefined;
    }

    const url = buildCatalogUrl({ region, dealType, propertyType, nearMetro });
    router.push(url);
  };

  return (
    <section className="relative z-10 min-h-[60vh] flex items-center">
      {/* Background */}
      <NextImage
        src={heroBg}
        alt="Городской пейзаж"
        fill
        priority
        className="object-cover"
      />
      <div className="absolute inset-0 bg-hero-overlay" />

      {/* Content */}
      <div className="relative container mx-auto px-4 pt-20 pb-12">
        <motion.div
          initial={{ opacity: 0, y: 30 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.7, ease: "easeOut" }}
          className="max-w-3xl lg:max-w-5xl"
        >
          <motion.span
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/15 text-primary text-sm font-medium mb-6 border border-primary/20"
          >
            <MapPin className="w-3.5 h-3.5" />
            {heroContent.badge}
          </motion.span>

          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold text-dark-fg leading-tight mb-6">
            <span className="text-gradient-primary">Недвижимость Беларуси{" "}</span>
            <br/>
            {" "}{heroContent.titleSuffix}
          </h1>

          <p className="text-lg text-dark-fg/60 mb-10 max-w-xl leading-relaxed">
            {heroContent.description}
          </p>

          {/* Search Card */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4, duration: 0.6 }}
            className="bg-dark-bg/60 backdrop-blur-xl rounded-2xl p-2 border border-dark-card max-w-2xl"
          >
            {/* Tabs */}
            <div className="flex gap-1 mb-2 px-1">
              {tabs.map((tab, i) => (
                <button
                  key={tab}
                  onClick={() => setActiveTab(i)}
                  className={`px-4 py-2 text-sm rounded-lg transition-all ${activeTab === i
                    ? "bg-primary text-primary-foreground font-medium"
                    : "text-dark-fg/50 hover:text-dark-fg hover:bg-dark-card"
                    }`}
                >
                  {tab}
                </button>
              ))}
            </div>

            {/* Search Row */}
            <div className="flex flex-col sm:flex-row gap-2">
              <div className="flex-1 relative" ref={searchRef}>
                <div className="flex items-center gap-3 bg-dark-card rounded-xl px-4 py-3">
                  <Search className="w-4 h-4 text-dark-muted flex-shrink-0" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    onFocus={() => setSearchFocused(true)}
                    onBlur={() => {
                      setSearchFocused(false);
                      resolveAndSyncLocation();
                    }}
                    onKeyDown={(e) => {
                      if (e.key === "Enter") {
                        e.preventDefault();
                        handleSearch();
                      }
                    }}
                    placeholder="Город или метро..."
                    className="bg-transparent text-dark-fg placeholder:text-dark-muted text-sm w-full outline-none"
                  />
                  {searchQuery && (
                    <button
                      onClick={() => {
                        setSearchQuery("");
                        setSearchFocused(false);
                      }}
                      className="flex-shrink-0 p-0.5 rounded-full hover:bg-dark-muted/20 transition-colors"
                    >
                      <X className="w-3.5 h-3.5 text-dark-muted" />
                    </button>
                  )}
                </div>

                <AnimatePresence>
                  {showAutocomplete && (
                    <motion.div
                      initial={{ opacity: 0, y: -4 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: -4 }}
                      transition={{ duration: 0.15 }}
                      className="absolute top-full left-0 right-0 mt-1 bg-card rounded-xl border border-border shadow-card-hover overflow-hidden z-50 max-h-72 overflow-y-auto"
                    >
                      {Array.from(grouped.entries()).map(([type, items]) => (
                        <div key={type}>
                          <div className="px-4 py-1.5 text-xs font-medium text-muted-foreground uppercase tracking-wider bg-muted/50">
                            {typeLabel(type)}
                          </div>
                          {items.map((s) => (
                            <button
                              type="button"
                              key={s.label}
                              onMouseDown={(e) => e.preventDefault()}
                              onClick={() => {
                                setSearchQuery(s.label);
                                setSearchFocused(false);
                              }}
                              className="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-foreground hover:bg-muted transition-colors"
                            >
                              {suggestionIcon(s.type)}
                              <span>{s.label}</span>
                            </button>
                          ))}
                        </div>
                      ))}
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>

              {/* Property type dropdown */}
              <div className="relative min-w-0 sm:min-w-[13rem] sm:w-auto sm:max-w-[min(100%,20rem)]" ref={dropdownRef}>
                <button
                  onClick={() => setTypeOpen(!typeOpen)}
                  className="flex items-center gap-2 bg-dark-card rounded-xl px-4 py-3 cursor-pointer w-full text-left"
                >
                  <Home className="w-4 h-4 text-dark-muted flex-shrink-0" />
                  <span className={`text-sm flex-1 min-w-0 text-left leading-snug [overflow-wrap:anywhere] ${effectiveSelectedType === "all" ? "text-dark-muted" : "text-dark-fg"}`}>
                    {selectedLabel}
                  </span>
                  <ChevronDown className={`w-3.5 h-3.5 text-dark-muted flex-shrink-0 transition-transform ${typeOpen ? "rotate-180" : ""}`} />
                </button>

                <AnimatePresence>
                  {typeOpen && (
                    <motion.div
                      initial={{ opacity: 0, y: -4 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, y: -4 }}
                      transition={{ duration: 0.15 }}
                      className="absolute top-full left-0 right-0 mt-1 bg-card rounded-xl border border-border shadow-card-hover overflow-hidden z-50 w-full min-w-0"
                    >
                      {availablePropertyTypes.map((t) => (
                        <button
                          key={t.value}
                          type="button"
                          onClick={() => { setSelectedType(t.value); setTypeOpen(false); }}
                          className="flex items-start gap-2 w-full px-4 py-2.5 text-sm text-foreground hover:bg-muted transition-colors text-left"
                        >
                          <Check className={`w-3.5 h-3.5 flex-shrink-0 mt-0.5 ${effectiveSelectedType === t.value ? "opacity-100 text-primary" : "opacity-0"}`} />
                          <span className="min-w-0 flex-1 leading-snug [overflow-wrap:anywhere]">{t.label}</span>
                        </button>
                      ))}
                    </motion.div>
                  )}
                </AnimatePresence>
              </div>

              <Button
                onClick={handleSearch}
                className="bg-gradient-primary text-primary-foreground shadow-primary hover:opacity-90 transition-opacity rounded-xl px-6 h-12 border-0"
              >
                <Search className="w-4 h-4 mr-2" />
                Найти
              </Button>
            </div>
          </motion.div>

        </motion.div>
      </div>
    </section>
  );
};

export default HeroSection;
