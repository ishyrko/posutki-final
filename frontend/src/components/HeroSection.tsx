"use client";

import { useState } from "react";
import { Search, MapPin, Users, Home, Minus, Plus } from "lucide-react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import NextImage from "next/image";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { buildCatalogUrl } from "@/features/catalog/slugs";
import { MAX_DAILY_GUESTS } from "@/features/create-listing/validation";

const CITY_HREFS: { label: string; href: string }[] = [
  { label: "Минск", href: "/" },
  { label: "Гродно", href: "/grodno/kvartiry/" },
  { label: "Брест", href: "/brest/kvartiry/" },
  { label: "Витебск", href: "/vitebsk/kvartiry/" },
  { label: "Гомель", href: "/gomel/kvartiry/" },
  { label: "Могилёв", href: "/mogilev/kvartiry/" },
];

type CatalogPropertyType = "apartment" | "house";

const MIN_GUESTS = 1;

/** Минск и областные центры в URL каталога. */
const HERO_REGION_CITIES: { value: string; label: string; region?: string }[] = [
  { value: "minsk", label: "Минск" },
  { value: "brest", label: "Брест", region: "brest" },
  { value: "vitebsk", label: "Витебск", region: "vitebsk" },
  { value: "gomel", label: "Гомель", region: "gomel" },
  { value: "grodno", label: "Гродно", region: "grodno" },
  { value: "mogilev", label: "Могилёв", region: "mogilev" },
];

function buildHeroCatalogHref(cityValue: string, propertyType: CatalogPropertyType): string {
  const row = HERO_REGION_CITIES.find((c) => c.value === cityValue);
  if (!row) {
    return buildCatalogUrl({ propertyType });
  }
  return buildCatalogUrl({
    ...(row.region ? { region: row.region } : {}),
    propertyType,
  });
}

function clampGuests(n: number): number {
  return Math.min(MAX_DAILY_GUESTS, Math.max(MIN_GUESTS, n));
}

const HeroSection = () => {
  const router = useRouter();
  const [propertyType, setPropertyType] = useState<CatalogPropertyType>("apartment");
  const [cityValue, setCityValue] = useState("minsk");
  const [guestCount, setGuestCount] = useState(2);

  const handleFind = () => {
    router.push(buildHeroCatalogHref(cityValue, propertyType));
  };

  return (
    <section className="relative overflow-hidden">
      <div className="absolute inset-0">
        <NextImage
          src="/hero-apartment.jpg"
          alt="Современная квартира"
          fill
          priority
          className="object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-b from-foreground/60 via-foreground/40 to-foreground/70" />
      </div>

      <div className="relative container mx-auto px-4 py-24 md:py-36 lg:py-44">
        <div className="max-w-3xl mx-auto text-center mb-10">
          <h1 className="font-display text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-primary-foreground mb-4 text-balance">
            Найдите идеальное жильё в Беларуси
          </h1>
          <p className="text-lg md:text-xl text-primary-foreground/80 font-body">
            Тысячи проверенных квартир и домов для посуточной аренды
          </p>
        </div>

        <div className="max-w-4xl mx-auto">
          <form
            className="bg-card rounded-2xl shadow-elevated p-3 md:p-4"
            onSubmit={(e) => {
              e.preventDefault();
              handleFind();
            }}
          >
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-surface">
                <Home className="h-5 w-5 text-primary shrink-0" />
                <div className="flex-1 min-w-0">
                  <label htmlFor="hero-property-type" className="text-xs font-medium text-muted-foreground block">
                    Тип жилья
                  </label>
                  <Select
                    value={propertyType}
                    onValueChange={(v) => setPropertyType(v as CatalogPropertyType)}
                  >
                    <SelectTrigger
                      id="hero-property-type"
                      className="h-auto min-h-0 border-0 bg-transparent p-0 shadow-none ring-0 ring-offset-0 focus:ring-0 w-full justify-between gap-1 text-sm font-medium text-foreground [&>svg]:shrink-0"
                    >
                      <SelectValue placeholder="Квартира" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="apartment">Квартира</SelectItem>
                      <SelectItem value="house">Дом</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-surface">
                <MapPin className="h-5 w-5 text-primary shrink-0" />
                <div className="flex-1 min-w-0">
                  <label htmlFor="hero-city" className="text-xs font-medium text-muted-foreground block">
                    Город
                  </label>
                  <Select value={cityValue} onValueChange={setCityValue}>
                    <SelectTrigger
                      id="hero-city"
                      className="h-auto min-h-0 border-0 bg-transparent p-0 shadow-none ring-0 ring-offset-0 focus:ring-0 w-full justify-between gap-1 text-sm font-medium text-foreground [&>svg]:shrink-0"
                    >
                      <SelectValue placeholder="Город" />
                    </SelectTrigger>
                    <SelectContent>
                      {HERO_REGION_CITIES.map((c) => (
                        <SelectItem key={c.value} value={c.value}>
                          {c.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-surface">
                <Users className="h-5 w-5 text-primary shrink-0" />
                <div className="flex-1 min-w-0">
                  <label htmlFor="hero-guests" className="text-xs font-medium text-muted-foreground block mb-1">
                    Гости
                  </label>
                  <div className="flex items-center gap-0.5">
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 shrink-0 text-foreground hover:text-foreground"
                      aria-label="Меньше гостей"
                      disabled={guestCount <= MIN_GUESTS}
                      onClick={() => setGuestCount((n) => clampGuests(n - 1))}
                    >
                      <Minus className="h-4 w-4" />
                    </Button>
                    <input
                      id="hero-guests"
                      name="guests"
                      type="number"
                      inputMode="numeric"
                      min={MIN_GUESTS}
                      max={MAX_DAILY_GUESTS}
                      step={1}
                      value={guestCount}
                      onChange={(e) => {
                        const v = e.target.valueAsNumber;
                        if (Number.isNaN(v)) return;
                        setGuestCount(clampGuests(v));
                      }}
                      onBlur={() => setGuestCount((n) => clampGuests(n))}
                      className="min-w-0 w-10 flex-1 max-w-[3.25rem] bg-transparent text-center text-sm font-medium text-foreground tabular-nums outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                    />
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="h-8 w-8 shrink-0 text-foreground hover:text-foreground"
                      aria-label="Больше гостей"
                      disabled={guestCount >= MAX_DAILY_GUESTS}
                      onClick={() => setGuestCount((n) => clampGuests(n + 1))}
                    >
                      <Plus className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>

              <Button
                type="submit"
                className="h-full min-h-[52px] rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 text-base font-semibold gap-2 shadow-lg"
              >
                <Search className="h-5 w-5" />
                Найти
              </Button>
            </div>
          </form>

          <div className="flex flex-wrap justify-center gap-2 mt-6">
            {CITY_HREFS.map((city) => (
              <Link
                key={city.label}
                href={city.href}
                className="px-4 py-2 rounded-full bg-primary-foreground/15 backdrop-blur-sm text-primary-foreground text-sm font-medium hover:bg-primary-foreground/25 transition-colors duration-150"
              >
                {city.label}
              </Link>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};

export default HeroSection;
