"use client";

import { useMemo } from "react";
import { motion } from "framer-motion";
import ArticleCard from "./ArticleCard";
import { ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import Link from "next/link";
import type { Article } from "@/features/articles/types";
import { articleToCardData } from "@/features/articles/articleCardDisplay";

const HOME_ARTICLES_LIMIT = 3;

interface ArticlesSectionProps {
  articles?: Article[];
}

const ArticlesSection = ({ articles = [] }: ArticlesSectionProps) => {
  const cards = useMemo(
    () => articles.slice(0, HOME_ARTICLES_LIMIT).map(articleToCardData),
    [articles],
  );
  return (
    <section className="py-12 md:py-14 bg-background">
      <div className="container mx-auto px-4">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          className="flex items-end justify-between mb-8"
        >
          <div>
            <h2 className="text-3xl font-bold text-foreground font-display mb-2">Свежие статьи</h2>
            <p className="text-muted-foreground">Экспертные материалы о недвижимости</p>
          </div>
          <Button variant="ghost" className="hidden sm:flex items-center gap-2 text-primary hover:text-primary" asChild>
            <Link href="/stati/">
              Все статьи
              <ArrowRight className="w-4 h-4" />
            </Link>
          </Button>
        </motion.div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {cards.map((article, i) => (
            <ArticleCard key={`${article.slug}-${i}`} {...article} index={i} />
          ))}
        </div>
      </div>
    </section>
  );
};

export default ArticlesSection;
