"use client";

import { Building2, TreePine, Waves, Castle, Factory, Landmark } from "lucide-react";
import Link from "next/link";

const cities = [
  { name: "Минск", slug: "minsk", count: "2000+", icon: Building2, href: "/" },
  { name: "Гродно", slug: "grodno", count: "300+", icon: Castle, href: "/grodno/kvartiry/" },
  { name: "Брест", slug: "brest", count: "250+", icon: Landmark, href: "/brest/kvartiry/" },
  { name: "Витебск", slug: "vitebsk", count: "200+", icon: Waves, href: "/vitebsk/kvartiry/" },
  { name: "Гомель", slug: "gomel", count: "240+", icon: Factory, href: "/gomel/kvartiry/" },
  { name: "Могилёв", slug: "mogilev", count: "180+", icon: TreePine, href: "/mogilev/kvartiry/" },
];

const CitySection = () => {
  return (
    <section className="py-16 md:py-24 bg-surface">
      <div className="container mx-auto px-4">
        <h2 className="font-display text-2xl md:text-3xl font-bold text-foreground mb-2 text-center">
          Выберите город
        </h2>
        <p className="text-muted-foreground text-center mb-10">
          Посуточная аренда по всей Беларуси
        </p>

        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {cities.map((city) => {
            const Icon = city.icon;
            return (
              <Link
                key={city.slug}
                href={city.href}
                className="group flex flex-col items-center gap-3 p-6 rounded-xl bg-card shadow-card hover:shadow-card-hover transition-all duration-200 hover:-translate-y-1"
              >
                <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors duration-150">
                  <Icon className="h-6 w-6 text-primary" />
                </div>
                <div className="text-center">
                  <p className="font-display font-semibold text-foreground">{city.name}</p>
                  <p className="text-sm text-muted-foreground">{city.count} объектов</p>
                </div>
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default CitySection;
