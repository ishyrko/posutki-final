"use client";

import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import { cn } from "@/lib/utils";
import type { FaqItem } from "@/lib/json-ld/faq";

type EmbeddedFaqSectionProps = {
  items: FaqItem[];
  title?: string;
  /** `catalog` — как заголовок SEO-блока под каталогом (слева, компактнее). */
  variant?: "default" | "catalog";
};

/** FAQ-блок для встраивания под текстом статической страницы. */
export function EmbeddedFaqSection({
  items,
  title = "Частые вопросы",
  variant = "default",
}: EmbeddedFaqSectionProps) {
  return (
    <div className="border-t border-border pt-8">
      <h2
        className={cn(
          "font-display font-bold text-foreground",
          variant === "catalog"
            ? "mb-4 text-xl md:text-2xl"
            : "mb-6 text-center text-2xl md:text-3xl",
        )}
      >
        {title}
      </h2>

      <Accordion type="single" collapsible className="w-full">
        {items.map((item, index) => (
          <AccordionItem key={item.question} value={`item-${index}`}>
            <AccordionTrigger className="text-left font-display">
              {item.question}
            </AccordionTrigger>
            <AccordionContent className="text-muted-foreground">
              {item.answer}
            </AccordionContent>
          </AccordionItem>
        ))}
      </Accordion>
    </div>
  );
}
