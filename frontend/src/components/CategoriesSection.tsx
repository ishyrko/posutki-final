"use client";

import { motion } from "framer-motion";
import { TrendingUp, BookOpen, Tag, PiggyBank, Scale, MapPin, Paintbrush, ChevronLeft, ChevronRight } from "lucide-react";
import { useRef, useState, useEffect } from "react";
import Link from "next/link";

const categories = [
  { icon: TrendingUp, label: "Рынок", description: "Аналитика и тренды", color: "bg-primary/10 text-primary", slug: "rynok" },
  { icon: BookOpen, label: "Покупателям", description: "Советы и гиды", color: "bg-accent text-accent-foreground", slug: "pokupatelyam" },
  { icon: Tag, label: "Продавцам", description: "Стратегии продаж", color: "bg-primary/10 text-primary", slug: "prodavtsam" },
  { icon: PiggyBank, label: "Инвестиции", description: "ROI и доходность", color: "bg-accent text-accent-foreground", slug: "investitsii" },
  { icon: Scale, label: "Право", description: "Законы и договоры", color: "bg-primary/10 text-primary", slug: "pravo" },
  { icon: MapPin, label: "Районы", description: "Обзоры локаций", color: "bg-accent text-accent-foreground", slug: "rayony" },
  { icon: Paintbrush, label: "Ремонт", description: "Идеи и сметы", color: "bg-primary/10 text-primary", slug: "remont" },
];

const CategoriesSection = () => {
  const scrollRef = useRef<HTMLDivElement>(null);
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(true);

  const checkScroll = () => {
    const el = scrollRef.current;
    if (!el) return;
    setCanScrollLeft(el.scrollLeft > 4);
    setCanScrollRight(el.scrollLeft < el.scrollWidth - el.clientWidth - 4);
  };

  useEffect(() => {
    checkScroll();
    const el = scrollRef.current;
    if (el) el.addEventListener("scroll", checkScroll, { passive: true });
    window.addEventListener("resize", checkScroll);
    return () => {
      el?.removeEventListener("scroll", checkScroll);
      window.removeEventListener("resize", checkScroll);
    };
  }, []);

  const scroll = (dir: "left" | "right") => {
    const el = scrollRef.current;
    if (!el) return;
    el.scrollBy({ left: dir === "left" ? -260 : 260, behavior: "smooth" });
  };

  return (
    <section className="py-12 bg-background">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="text-center mb-8"
        >
          <h2 className="text-3xl font-bold text-foreground font-display mb-2">Полезные статьи</h2>
          <p className="text-muted-foreground">Экспертные материалы по всем аспектам недвижимости</p>
        </motion.div>

        <div className="relative group">
          {/* Left arrow */}
          <button
            onClick={() => scroll("left")}
            className={`absolute -left-2 sm:-left-4 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-card border border-border shadow-card flex items-center justify-center transition-all duration-200 hover:shadow-card-hover hover:border-primary/30 ${canScrollLeft ? "opacity-100" : "opacity-0 pointer-events-none"
              }`}
            aria-label="Прокрутить влево"
          >
            <ChevronLeft className="w-4 h-4 text-foreground" />
          </button>

          {/* Right arrow */}
          <button
            onClick={() => scroll("right")}
            className={`absolute -right-2 sm:-right-4 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-card border border-border shadow-card flex items-center justify-center transition-all duration-200 hover:shadow-card-hover hover:border-primary/30 ${canScrollRight ? "opacity-100" : "opacity-0 pointer-events-none"
              }`}
            aria-label="Прокрутить вправо"
          >
            <ChevronRight className="w-4 h-4 text-foreground" />
          </button>

          {/* Fade edges */}
          {canScrollLeft && (
            <div className="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-background to-transparent z-[1] pointer-events-none" />
          )}
          {canScrollRight && (
            <div className="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-background to-transparent z-[1] pointer-events-none" />
          )}

          <div
            ref={scrollRef}
            className="flex gap-3 overflow-x-auto overflow-y-hidden scrollbar-hide scroll-smooth"
          >
            {categories.map((cat, i) => {
              const Icon = cat.icon;
              return (
                <motion.div
                  key={cat.label}
                  initial={{ opacity: 0, y: 10 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ delay: i * 0.05 }}
                  className="flex-shrink-0"
                >
                  <Link
                    href={`/stati/${cat.slug}/`}
                    className="group/item flex w-[220px] min-h-[82px] cursor-pointer items-center gap-2.5 rounded-xl border border-border bg-card px-4 py-2.5 shadow-card transition-all hover:border-primary/20 hover:shadow-card-hover"
                  >
                    <div className={`h-8.5 w-8.5 rounded-lg ${cat.color} flex items-center justify-center`}>
                      <Icon className="h-4 w-4" />
                    </div>
                    <div className="text-left">
                      <div className="text-sm font-medium text-foreground">{cat.label}</div>
                      <div className="line-clamp-2 text-xs text-muted-foreground">{cat.description}</div>
                    </div>
                  </Link>
                </motion.div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
};

export default CategoriesSection;
