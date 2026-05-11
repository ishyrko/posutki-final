"use client";

import { Building2, TreePine, Waves, Castle, Factory, Landmark } from "lucide-react";
import Link from "next/link";
import { buildCatalogUrl } from "@/features/catalog/slugs";

const cities = [
  /** Минск и область — без префикса региона в URL, как в каталоге. */
  { name: "Минск", slug: "minsk", icon: Building2, href: buildCatalogUrl({ propertyType: "apartment" }) },
  { name: "Гродно", slug: "grodno", icon: Castle, href: "/grodno/kvartiry/" },
  { name: "Брест", slug: "brest", icon: Landmark, href: "/brest/kvartiry/" },
  { name: "Витебск", slug: "vitebsk", icon: Waves, href: "/vitebsk/kvartiry/" },
  { name: "Гомель", slug: "gomel", icon: Factory, href: "/gomel/kvartiry/" },
  { name: "Могилёв", slug: "mogilev", icon: TreePine, href: "/mogilev/kvartiry/" },
];

const CitySection = () => {
  return (
    <section className="bg-surface pt-12 pb-10 md:pt-14 md:pb-6 lg:pt-16 lg:pb-8">
      <div className="container mx-auto px-4">
        <h2 className="font-display text-2xl md:text-3xl font-bold text-foreground mb-2 text-center">
          Выберите город
        </h2>
        <p className="text-muted-foreground text-center mb-6 md:mb-8">
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
                <p className="font-display font-semibold text-foreground text-center">{city.name}</p>
              </Link>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default CitySection;
