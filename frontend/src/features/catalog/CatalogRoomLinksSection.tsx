import Link from "next/link";
import {
  buildRoomCatalogUrl,
  buildRoomBreadcrumbLabel,
  resolveCatalogCitySlug,
  ROOM_BUCKET_VALUES,
  type ParsedSegments,
  type RoomBucket,
} from "@/features/catalog/slugs";
import { cn } from "@/lib/utils";

type CatalogRoomLinksSectionProps = {
  parsed: ParsedSegments;
  activeBucket?: RoomBucket;
  className?: string;
};

/** Перелинковка path-лендингов по числу комнат для города. */
export default function CatalogRoomLinksSection({
  parsed,
  activeBucket,
  className,
}: CatalogRoomLinksSectionProps) {
  const citySlug = resolveCatalogCitySlug(parsed);

  return (
    <section className={cn("mt-8 border-t border-border pt-6", className)}>
      <h2 className="font-display font-semibold text-lg text-foreground mb-3">
        По числу комнат
      </h2>
      <div className="flex flex-wrap gap-2">
        {ROOM_BUCKET_VALUES.map((bucket) => {
          const href = buildRoomCatalogUrl(citySlug, bucket);
          const isActive = activeBucket === bucket;
          return (
            <Link
              key={bucket}
              href={href}
              className={cn(
                "rounded-lg border px-3 py-2 text-sm font-medium transition-colors",
                isActive
                  ? "border-primary bg-primary text-primary-foreground"
                  : "border-border bg-surface text-foreground hover:bg-muted",
              )}
            >
              {buildRoomBreadcrumbLabel(bucket)}
            </Link>
          );
        })}
      </div>
    </section>
  );
}
