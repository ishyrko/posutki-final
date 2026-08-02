import { RichContentHtml } from "@/components/RichContentHtml";

type CatalogCitySeoSectionProps = {
  heading: string;
  html: string;
};

export default function CatalogCitySeoSection({ heading, html }: CatalogCitySeoSectionProps) {
  return (
    <section className="mt-10 pt-8 border-t border-border">
      <h2 className="font-display font-bold text-xl md:text-2xl text-foreground mb-4">
        {heading}
      </h2>
      <RichContentHtml
        html={html}
        className="prose prose-sm max-w-none prose-neutral prose-headings:font-semibold prose-p:text-muted-foreground prose-li:text-muted-foreground dark:prose-invert [&_a]:text-primary [&_a]:underline"
      />
    </section>
  );
}
