"use client";

import type { ReactNode } from "react";
import { motion } from "framer-motion";
import { Heart, MapPin, BedDouble, Bath, Star, Users } from "lucide-react";
import Link from "next/link";
import { formatBynWithUsd } from "@/lib/currency";
import { useToggleFavorite, useFavoriteIds } from "@/features/properties/hooks";
import { useUser } from "@/features/auth/hooks";
import { useRouter } from "next/navigation";
import { buildPropertyUrl } from "@/features/catalog/slugs";
import { PROPERTY_TYPE_NOMINATIVE_DAILY } from "@/features/properties/property-deal-heading";
import { placementBadgeLabel } from "@/features/placement/types";

interface PropertyCardProps {
  id: number;
  image: string;
  price: ReactNode;
  /** BYN equivalent for the secondary USD line when `secondaryPrice` is omitted (non-daily). */
  primaryBynAmount?: number;
  /** Approximate price in listing currency (or USD if listing was in BYN). */
  secondaryPrice?: string;
  title: string;
  address: string;
  beds: number;
  baths: number;
  area: number;
  /** Для посуточной аренды — макс. гостей */
  maxGuests?: number | null;
  /** Подпись с бэка; иначе берётся из `propertyType`. */
  typeLabel?: string | null;
  index?: number;
  dealType?: string;
  propertyType?: string;
  /** Slug региона для URL (brest, vitebsk, …); Минск — не передавать. */
  regionSlug?: string;
  rating?: number | null;
  reviewCount?: number | null;
  /** When false, skip fade-in on mount (reduces Safari flicker when parent re-renders after auth/rates). */
  animateEntrance?: boolean;
  placementType?: string | null;
  placementSlotRank?: number | null;
}

const PropertyCard = ({
  id,
  image,
  price,
  primaryBynAmount,
  secondaryPrice,
  title,
  address,
  beds,
  baths,
  area,
  maxGuests,
  typeLabel,
  index = 0,
  dealType,
  propertyType,
  regionSlug,
  rating,
  reviewCount,
  animateEntrance = true,
  placementType,
  placementSlotRank,
}: PropertyCardProps) => {
  const { data: user } = useUser();
  const { data: favoriteIds = [] } = useFavoriteIds();
  const { mutate: toggleFavorite } = useToggleFavorite();
  const router = useRouter();
  const isFavorited = favoriteIds.includes(id);

  const handleFavoriteClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (!user) {
      router.push("/login");
      return;
    }
    toggleFavorite({ propertyId: id, isFavorited });
  };

  const href = buildPropertyUrl(propertyType, id, regionSlug);
  const isDaily = dealType === "daily";
  const showRating = rating != null && rating > 0;
  const imageTypeBadge =
    typeLabel?.trim() || (propertyType ? PROPERTY_TYPE_NOMINATIVE_DAILY[propertyType] : undefined);
  const topBadge = placementBadgeLabel(placementType, placementSlotRank);

  return (
    <Link href={href} className="block h-full">
      <motion.div
        initial={animateEntrance ? { opacity: 0, y: 20 } : false}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: Math.min(index, 12) * 0.1, duration: 0.5 }}
        className="group h-full flex flex-col rounded-xl overflow-hidden bg-card shadow-card hover:shadow-card-hover transition-all duration-300 cursor-pointer"
      >
        <div className="relative aspect-[4/3] overflow-hidden">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={image}
            alt={title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            loading="lazy"
          />

          <div className="absolute top-3 left-3 flex flex-col gap-1.5 items-start">
            {topBadge && (
              <span className="px-2.5 py-1 rounded-full bg-amber-500 text-white text-xs font-bold shadow-sm">
                {topBadge}
              </span>
            )}
            {imageTypeBadge && (
              <span className="px-2.5 py-1 rounded-full bg-primary text-primary-foreground text-xs font-semibold">
                {imageTypeBadge}
              </span>
            )}
          </div>

          <button
            type="button"
            onClick={handleFavoriteClick}
            className={`absolute top-3 right-3 w-8 h-8 rounded-full backdrop-blur-sm flex items-center justify-center transition-colors z-10 cursor-pointer ${
              isFavorited ? "bg-primary text-white" : "bg-card/80 hover:bg-card text-foreground/70"
            }`}
          >
            <Heart className={`w-4 h-4 ${isFavorited ? "fill-current" : ""}`} />
          </button>
        </div>

        <div className="p-4 flex flex-col flex-1">
          <div className="flex items-start justify-between gap-2 mb-1">
            <h3 className="font-display font-semibold text-base text-foreground leading-tight line-clamp-2 min-h-[2.5rem]">
              {title}
            </h3>
            {showRating && (
              <div className="flex items-center gap-1 shrink-0">
                <Star className="h-4 w-4 fill-amber-400 text-amber-400" />
                <span className="text-sm font-semibold text-foreground">{rating.toFixed(1)}</span>
                {reviewCount != null && reviewCount > 0 && (
                  <span className="text-sm text-muted-foreground">({reviewCount})</span>
                )}
              </div>
            )}
          </div>

          <p className="text-xs text-muted-foreground flex items-center gap-1 mb-3 line-clamp-1">
            <MapPin className="w-3 h-3 shrink-0" />
            {address}
          </p>

          <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground mb-3">
            <span className="flex items-center gap-1">
              <BedDouble className="w-3.5 h-3.5" />
              {beds} комн.
            </span>
            {isDaily && maxGuests != null && maxGuests > 0 && (
              <>
                <span className="w-1 h-1 rounded-full bg-border shrink-0" />
                <span className="flex items-center gap-1">
                  <Users className="w-3.5 h-3.5" />
                  до {maxGuests} гостей
                </span>
              </>
            )}
            {!isDaily && (
              <>
                <span className="flex items-center gap-1">
                  <Bath className="w-3.5 h-3.5" />
                  {baths} сан.
                </span>
                <span className="flex items-center gap-1">
                  {`${area} м²`}
                </span>
              </>
            )}
          </div>

          <div className="flex items-baseline gap-1 mt-auto pt-3 border-t border-border">
            <span className="font-display text-lg font-bold text-foreground">{price}</span>
            {isDaily && <span className="text-sm text-muted-foreground">/ сутки</span>}
            {!isDaily && (
              <span className="text-xs text-muted-foreground">
                {secondaryPrice ?? (primaryBynAmount != null ? formatBynWithUsd(primaryBynAmount).usd : null)}
              </span>
            )}
          </div>
          {isDaily && secondaryPrice && (
            <span className="text-xs text-muted-foreground mt-0.5">{secondaryPrice}</span>
          )}
        </div>
      </motion.div>
    </Link>
  );
};

export default PropertyCard;
