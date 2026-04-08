"use client";

import { motion } from "framer-motion";
import { Heart, MapPin, Maximize, BedDouble, Bath } from "lucide-react";
import Link from "next/link";
import { parseBynPrice, formatBynWithUsd } from "@/lib/currency";
import { useToggleFavorite, useFavoriteIds } from "@/features/properties/hooks";
import { useUser } from "@/features/auth/hooks";
import { useRouter } from "next/navigation";
import { buildPropertyUrl } from "@/features/catalog/slugs";

interface PropertyCardProps {
  id: number;
  image: string;
  price: string;
  /** Approximate price in listing currency (or USD if listing was in BYN). */
  secondaryPrice?: string;
  title: string;
  address: string;
  beds: number;
  baths: number;
  area: number;
  tag?: string;
  index?: number;
  dealType?: string;
  propertyType?: string;
  /** When false, skip fade-in on mount (reduces Safari flicker when parent re-renders after auth/rates). */
  animateEntrance?: boolean;
}

const PropertyCard = ({ id, image, price, secondaryPrice, title, address, beds, baths, area, tag, index = 0, dealType, propertyType, animateEntrance = true }: PropertyCardProps) => {
  const { data: user } = useUser();
  const { data: favoriteIds = [] } = useFavoriteIds();
  const { mutate: toggleFavorite } = useToggleFavorite();
  const router = useRouter();
  const isFavorited = favoriteIds.includes(id);

  const handleFavoriteClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (!user) {
      router.push('/login');
      return;
    }
    toggleFavorite({ propertyId: id, isFavorited });
  };

  const href = buildPropertyUrl(dealType, propertyType, id);

  return (
    <Link href={href} className="block h-full">
      <motion.div
        initial={animateEntrance ? { opacity: 0, y: 20 } : false}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: Math.min(index, 12) * 0.1, duration: 0.5 }}
        className="group h-full flex flex-col bg-card rounded-2xl overflow-hidden shadow-card hover:shadow-card-hover transition-all duration-300 cursor-pointer"
      >
        {/* Image */}
        <div className="relative aspect-[4/3] overflow-hidden">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={image}
            alt={title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            loading="lazy"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-foreground/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />

          {tag && (
            <span className="absolute top-3 left-3 px-2.5 py-1 rounded-full bg-primary text-primary-foreground text-xs font-medium">
              {tag}
            </span>
          )}

          <button
            type="button"
            onClick={handleFavoriteClick}
            className={`absolute top-3 right-3 w-8 h-8 rounded-full backdrop-blur-sm flex items-center justify-center transition-colors ${
              isFavorited
                ? 'bg-primary text-white'
                : 'bg-card/80 hover:bg-card text-foreground/70'
            }`}
          >
            <Heart className={`w-4 h-4 ${isFavorited ? 'fill-current' : ''}`} />
          </button>
        </div>

        {/* Content */}
        <div className="p-4 flex flex-col flex-1">
          <div className="flex items-start justify-between mb-2">
            <div>
              <h3 className="font-semibold text-foreground text-lg">{price}</h3>
              <span className="text-xs text-muted-foreground">
                {secondaryPrice ?? formatBynWithUsd(parseBynPrice(price)).usd}
              </span>
            </div>
          </div>
          <p className="text-sm font-medium text-foreground/80 mb-1 line-clamp-2 min-h-[2.5rem]">{title}</p>
          <p className="text-xs text-muted-foreground flex items-center gap-1 mb-3 line-clamp-1">
            <MapPin className="w-3 h-3" />
            {address}
          </p>

          <div className="flex items-center gap-4 pt-3 border-t border-border mt-auto">
            <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <BedDouble className="w-3.5 h-3.5" />
              {beds} комн.
            </span>
            <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <Bath className="w-3.5 h-3.5" />
              {baths} сан.
            </span>
            <span className="flex items-center gap-1.5 text-xs text-muted-foreground">
              <Maximize className="w-3.5 h-3.5" />
              {propertyType === 'land' ? `${area} сот.` : `${area} м²`}
            </span>
          </div>
        </div>
      </motion.div>
    </Link>
  );
};

export default PropertyCard;
