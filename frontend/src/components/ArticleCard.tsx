"use client";

import { motion } from "framer-motion";
import { Clock, ArrowUpRight } from "lucide-react";
import Link from "next/link";

interface ArticleCardProps {
  slug?: string;
  categorySlug?: string;
  image: string;
  category: string;
  title: string;
  excerpt: string;
  date: string;
  readTime: string;
  index?: number;
}

const ArticleCard = ({ slug, categorySlug, image, category, title, excerpt, date, readTime, index = 0 }: ArticleCardProps) => {
  const href = slug ? `/stati/${categorySlug ? `${categorySlug}/` : ""}${slug}` : undefined;

  const imageBlock = (
    <div className="relative aspect-[16/10] rounded-2xl overflow-hidden mb-4">
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img src={image} alt={title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy" />
      <div className="absolute inset-0 bg-gradient-to-t from-foreground/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
      <span className="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-card/90 backdrop-blur-sm text-xs font-medium text-foreground">{category}</span>
      {slug ? null : (
        <div className="absolute top-3 right-3 w-8 h-8 rounded-full bg-card/80 backdrop-blur-sm flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
          <ArrowUpRight className="w-4 h-4 text-foreground" />
        </div>
      )}
    </div>
  );

  const bodyBlock = (
    <>
      <div className="flex items-center gap-3 text-xs text-muted-foreground mb-2">
        <span>{date}</span>
        <span className="w-1 h-1 rounded-full bg-muted-foreground/40" />
        <span className="flex items-center gap-1"><Clock className="w-3 h-3" />{readTime}</span>
      </div>
      <h3 className="font-display font-semibold text-foreground text-lg mb-2 group-hover:text-primary transition-colors leading-snug">{title}</h3>
      <p className="text-sm text-muted-foreground leading-relaxed line-clamp-2">{excerpt}</p>
    </>
  );

  return (
    <motion.article
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      transition={{ delay: index * 0.1, duration: 0.5 }}
      className="group relative cursor-pointer"
    >
      {slug ? (
        <>
          <Link href={href!} className="block">
            {imageBlock}
            {bodyBlock}
          </Link>
          <Link
            href={href!}
            target="_blank"
            rel="noopener noreferrer"
            className="absolute top-3 right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-card/80 opacity-0 backdrop-blur-sm transition-opacity group-hover:opacity-100"
            aria-label="Открыть статью в новой вкладке"
          >
            <ArrowUpRight className="w-4 h-4 text-foreground" />
          </Link>
        </>
      ) : (
        <>
          {imageBlock}
          {bodyBlock}
        </>
      )}
    </motion.article>
  );
};

export default ArticleCard;
