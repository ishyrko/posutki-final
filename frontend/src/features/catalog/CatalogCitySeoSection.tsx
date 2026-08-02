type CatalogCitySeoSectionProps = {
  heading: string;
  html: string;
};

/** SSR-only SEO block under the apartment catalog (no client hooks — avoids Radix useId hydration mismatch). */
export default function CatalogCitySeoSection({ heading, html }: CatalogCitySeoSectionProps) {
  return (
    <section className="mt-10 pt-8 border-t border-border">
      <h2 className="font-display font-bold text-xl md:text-2xl text-foreground mb-4">
        {heading}
      </h2>
      <div
        className="prose prose-sm max-w-none prose-neutral prose-headings:font-semibold prose-p:text-muted-foreground prose-li:text-muted-foreground dark:prose-invert [&_a]:text-primary [&_a]:underline [&_img]:my-6 [&_img]:block [&_img]:h-auto [&_img]:w-full [&_img]:rounded-lg"
        dangerouslySetInnerHTML={{ __html: html }}
      />
    </section>
  );
}
