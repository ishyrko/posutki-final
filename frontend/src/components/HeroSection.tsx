"use client";

import { useEffect, useState } from "react";
import { Search, MapPin, Home } from "lucide-react";
import { useRouter } from "next/navigation";
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
import { GuestCountControl } from "@/features/catalog/GuestCountControl";
import { GUESTS_QUERY_PARAM } from "@/features/catalog/guests-filter";

type CatalogPropertyType = "apartment" | "house";

/** Минск и областные центры в URL каталога. */
const HERO_REGION_CITIES: { value: string; label: string; region?: string }[] = [
  { value: "minsk", label: "Минск" },
  { value: "brest", label: "Брест", region: "brest" },
  { value: "vitebsk", label: "Витебск", region: "vitebsk" },
  { value: "gomel", label: "Гомель", region: "gomel" },
  { value: "grodno", label: "Гродно", region: "grodno" },
  { value: "mogilev", label: "Могилёв", region: "mogilev" },
];

const selectTriggerClassName =
  "h-auto min-h-0 border-0 bg-transparent p-0 shadow-none ring-0 ring-offset-0 focus:ring-0 w-full justify-between gap-1 text-sm font-medium text-foreground [&>svg]:shrink-0";

function buildHeroCatalogHref(cityValue: string, propertyType: CatalogPropertyType, guests: number): string {
  const row = HERO_REGION_CITIES.find((c) => c.value === cityValue);
  const path = buildCatalogUrl({
    ...(row?.region ? { region: row.region } : {}),
    propertyType,
  });
  const params = new URLSearchParams();
  params.set(GUESTS_QUERY_PARAM, String(guests));
  return `${path}?${params.toString()}`;
}

function propertyTypeLabel(value: CatalogPropertyType): string {
  return value === "house" ? "Дом" : "Квартира";
}

function cityLabel(value: string): string {
  return HERO_REGION_CITIES.find((c) => c.value === value)?.label ?? value;
}

const HeroSection = () => {
  const router = useRouter();
  const [propertyType, setPropertyType] = useState<CatalogPropertyType>("apartment");
  const [cityValue, setCityValue] = useState("minsk");
  const [guestCount, setGuestCount] = useState(2);
  // Radix Select generates unstable aria-controls ids across SSR/CSR — mount after hydration.
  const [selectsReady, setSelectsReady] = useState(false);

  useEffect(() => {
    setSelectsReady(true);
  }, []);

  const handleFind = () => {
    router.push(buildHeroCatalogHref(cityValue, propertyType, guestCount));
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

      <div className="relative container mx-auto px-4 py-12 md:py-16 lg:py-20">
        <div className="max-w-3xl mx-auto text-center mb-6 md:mb-8">
          <h1 className="font-display text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight text-primary-foreground mb-4 text-balance">
            Квартиры и дома на сутки в Беларуси
          </h1>
          <p className="text-lg md:text-xl text-primary-foreground/80 font-body">
            Квартиры и дома для посуточной аренды от владельцев
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
            <div className="grid grid-cols-1 gap-3 md:grid-cols-4 md:items-stretch">
              <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-surface">
                <Home className="h-5 w-5 text-primary shrink-0" />
                <div className="flex-1 min-w-0">
                  <label htmlFor="hero-property-type" className="text-xs font-medium text-muted-foreground block">
                    Тип жилья
                  </label>
                  {selectsReady ? (
                    <Select
                      value={propertyType}
                      onValueChange={(v) => setPropertyType(v as CatalogPropertyType)}
                    >
                      <SelectTrigger
                        id="hero-property-type"
                        className={selectTriggerClassName}
                      >
                        <SelectValue placeholder="Квартира" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="apartment">Квартира</SelectItem>
                        <SelectItem value="house">Дом</SelectItem>
                      </SelectContent>
                    </Select>
                  ) : (
                    <div
                      id="hero-property-type"
                      className="flex h-auto min-h-0 w-full items-center justify-between gap-1 text-sm font-medium text-foreground"
                      aria-hidden
                    >
                      {propertyTypeLabel(propertyType)}
                    </div>
                  )}
                </div>
              </div>

              <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-surface">
                <MapPin className="h-5 w-5 text-primary shrink-0" />
                <div className="flex-1 min-w-0">
                  <label htmlFor="hero-city" className="text-xs font-medium text-muted-foreground block">
                    Город
                  </label>
                  {selectsReady ? (
                    <Select value={cityValue} onValueChange={setCityValue}>
                      <SelectTrigger
                        id="hero-city"
                        className={selectTriggerClassName}
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
                  ) : (
                    <div
                      id="hero-city"
                      className="flex h-auto min-h-0 w-full items-center justify-between gap-1 text-sm font-medium text-foreground"
                      aria-hidden
                    >
                      {cityLabel(cityValue)}
                    </div>
                  )}
                </div>
              </div>

              <div className="px-4 py-3 rounded-xl bg-surface">
                <GuestCountControl
                  id="hero-guests"
                  value={guestCount}
                  onChange={setGuestCount}
                />
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
        </div>
      </div>
    </section>
  );
};

export default HeroSection;
