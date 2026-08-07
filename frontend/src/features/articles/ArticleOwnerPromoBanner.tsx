import Link from "next/link";
import { ArrowRight, Home } from "lucide-react";

const OWNER_LANDING_HREF = "/sdat-kvartiru-na-sutki/";

/** Promo plaque linking landlords from articles to the listing benefits landing. */
export function ArticleOwnerPromoBanner() {
  return (
    <Link
      href={OWNER_LANDING_HREF}
      className="mb-8 flex items-start gap-3 rounded-xl border border-primary/30 bg-primary/5 p-4 transition-colors hover:border-primary/50 hover:bg-primary/10 sm:items-center sm:gap-4"
    >
      <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
        <Home className="h-5 w-5" />
      </div>
      <div className="min-w-0 flex-1">
        <p className="text-sm font-medium leading-snug text-foreground sm:text-base">
          Посмотрите, какие преимущества даёт размещение объявления на Posutki.by
        </p>
        <span className="mt-1 inline-flex items-center gap-1 text-sm font-medium text-primary">
          Узнать больше
          <ArrowRight className="h-4 w-4" />
        </span>
      </div>
    </Link>
  );
}
