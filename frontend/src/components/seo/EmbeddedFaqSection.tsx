"use client";

import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import type { FaqItem } from "@/lib/json-ld/faq";

type EmbeddedFaqSectionProps = {
  items: FaqItem[];
  title?: string;
};

/** FAQ-блок для встраивания под текстом статической страницы. */
export function EmbeddedFaqSection({
  items,
  title = "Частые вопросы",
}: EmbeddedFaqSectionProps) {
  return (
    <div className="border-t border-border pt-8">
      <h2 className="mb-6 text-center font-display text-2xl font-bold text-foreground md:text-3xl">
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
