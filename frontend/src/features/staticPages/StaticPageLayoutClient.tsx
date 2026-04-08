"use client";

import { motion } from "framer-motion";

type StaticPageLayoutClientProps = {
  title: string;
  sanitizedHtml: string;
};

export default function StaticPageLayoutClient({
  title,
  sanitizedHtml,
}: StaticPageLayoutClientProps) {
  return (
    <section className="bg-background pt-24 pb-16">
      <div className="container mx-auto max-w-3xl px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="mb-10 text-center"
        >
          <h1 className="font-display text-3xl font-bold text-foreground md:text-4xl">
            {title}
          </h1>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="space-y-8 rounded-xl border border-border bg-card p-6 shadow-card md:p-10"
        >
          <div
            className="prose prose-sm max-w-none prose-neutral prose-headings:font-semibold prose-p:text-muted-foreground prose-li:text-muted-foreground dark:prose-invert [&_a]:text-primary [&_a]:underline"
            dangerouslySetInnerHTML={{ __html: sanitizedHtml }}
          />
        </motion.div>
      </div>
    </section>
  );
}
