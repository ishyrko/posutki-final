"use client";

import { Search, MapPin, CalendarDays, Users } from "lucide-react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import NextImage from "next/image";
import { Button } from "@/components/ui/button";

const CITY_HREFS: { label: string; href: string }[] = [
  { label: "Минск", href: "/" },
  { label: "Гродно", href: "/grodno/" },
  { label: "Брест", href: "/brest/" },
  { label: "Витебск", href: "/vitebsk/" },
  { label: "Гомель", href: "/gomel/" },
  { label: "Могилёв", href: "/mogilev/" },
];

interface HeroSectionProps {
  regionSlug?: string;
}

const HeroSection = ({ regionSlug: _regionSlug }: HeroSectionProps) => {
  const router = useRouter();

  const handleFind = () => {
    router.push("/kvartiry/");
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
          <div className="bg-card rounded-2xl shadow-elevated p-3 md:p-4">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
              <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-surface">
                <MapPin className="h-5 w-5 text-primary shrink-0" />
                <div className="flex-1 min-w-0">
                  <label className="text-xs font-medium text-muted-foreground block">Город</label>
                  <input
                    type="text"
                    placeholder="Минск"
                    className="w-full bg-transparent text-sm font-medium text-foreground placeholder:text-muted-foreground/60 outline-none"
                    readOnly
                  />
                </div>
              </div>

              <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-surface">
                <CalendarDays className="h-5 w-5 text-primary shrink-0" />
                <div className="flex-1 min-w-0">
                  <label className="text-xs font-medium text-muted-foreground block">Даты</label>
                  <input
                    type="text"
                    placeholder="Заезд — Выезд"
                    className="w-full bg-transparent text-sm font-medium text-foreground placeholder:text-muted-foreground/60 outline-none"
                    readOnly
                  />
                </div>
              </div>

              <div className="flex items-center gap-3 px-4 py-3 rounded-xl bg-surface">
                <Users className="h-5 w-5 text-primary shrink-0" />
                <div className="flex-1 min-w-0">
                  <label className="text-xs font-medium text-muted-foreground block">Гости</label>
                  <input
                    type="text"
                    placeholder="2 гостя"
                    className="w-full bg-transparent text-sm font-medium text-foreground placeholder:text-muted-foreground/60 outline-none"
                    readOnly
                  />
                </div>
              </div>

              <Button
                type="button"
                onClick={handleFind}
                className="h-full min-h-[52px] rounded-xl bg-primary text-primary-foreground hover:bg-primary/90 text-base font-semibold gap-2 shadow-lg"
              >
                <Search className="h-5 w-5" />
                Найти
              </Button>
            </div>
          </div>

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
