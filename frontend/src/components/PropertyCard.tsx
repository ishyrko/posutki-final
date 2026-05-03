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
  tag?: string;
  index?: number;
  dealType?: string;
  propertyType?: string;
  rating?: number | null;
  reviewCount?: number | null;
  /** When false, skip fade-in on mount (reduces Safari flicker when parent re-renders after auth/rates). */
  animateEntrance?: boolean;
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
  tag,
  index = 0,
  dealType,
  propertyType,
  rating,
  reviewCount,
  animateEntrance = true,
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

  const href = buildPropertyUrl(propertyType, id);
  const isDaily = dealType === "daily";
  const showRating = rating != null && rating > 0;

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

          {tag && (
            <span className="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-primary text-primary-foreground text-xs font-semibold">
              {tag}
            </span>
          )}

          <button
            type="button"
            onClick={handleFavoriteClick}
            className={`absolute top-3 right-3 w-8 h-8 rounded-full backdrop-blur-sm flex items-center justify-center transition-colors z-10 ${
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
