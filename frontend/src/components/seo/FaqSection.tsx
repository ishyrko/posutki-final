"use client";

import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
import type { FaqItem } from "@/lib/json-ld/faq";

type FaqSectionProps = {
  items: FaqItem[];
  title?: string;
  className?: string;
};

export function FaqSection({
  items,
  title = "Частые вопросы",
  className = "py-12 md:py-16 bg-background",
}: FaqSectionProps) {
  return (
    <section className={className}>
      <div className="container mx-auto px-4">
        <div className="mx-auto max-w-2xl">
          <h2 className="mb-8 text-center font-display text-2xl font-bold text-foreground md:text-3xl">
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
      </div>
    </section>
  );
}
