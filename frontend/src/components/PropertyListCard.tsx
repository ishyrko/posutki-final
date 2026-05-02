"use client";

import { motion } from "framer-motion";
import { Heart, MapPin, Maximize, BedDouble, Bath, Calendar, Building2, ArrowRight, TrainFront } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import type { NearbyMetroStation } from "@/features/metro/types";
import { useToggleFavorite, useFavoriteIds } from "@/features/properties/hooks";
import { useUser } from "@/features/auth/hooks";
import { useRouter } from "next/navigation";
import { buildPropertyUrl } from "@/features/catalog/slugs";
import { cn } from "@/lib/utils";

interface PropertyListCardProps {
  image: string;
  price: string;
  secondaryPrice?: string;
  title: string;
  address: string;
  beds?: number | null;
  baths?: number | null;
  area: number;
  landArea?: number | null;
  tag?: string;
  index?: number;
  description?: string;
  year?: number;
  floor?: string;
  id?: number;
  propertyType?: string;
  nearbyMetroStations?: NearbyMetroStation[];
  /** When true, metro badges stay below the address (e.g. catalog "list + map" on desktop). */
  metroOnSeparateLine?: boolean;
}

const lineColorClass = (line: number): string => {
  if (line === 1) return "bg-[#E3000B]";
  if (line === 2) return "bg-[#006DB7]";
  if (line === 3) return "bg-[#007A33]";
  return "bg-muted-foreground";
};

const formatDistance = (distanceKm: number): string => {
  if (distanceKm < 1) {
    const meters = Math.ceil((distanceKm * 1000) / 100) * 100;
    return `~${meters} м`;
  }

  return `~${distanceKm.toFixed(1)} км`;
};

const PropertyListCard = ({
  image,
  price,
  secondaryPrice,
  title,
  address,
  beds,
  baths,
  area,
  landArea,
  tag,
  index = 0,
  description,
  year,
  floor,
  id,
  propertyType,
  nearbyMetroStations = [],
  metroOnSeparateLine = false,
}: PropertyListCardProps) => {
  const href = id ? buildPropertyUrl(propertyType, id) : `/property/${index}`;
  const { data: user } = useUser();
  const { data: favoriteIds = [] } = useFavoriteIds();
  const { mutate: toggleFavorite } = useToggleFavorite();
  const router = useRouter();
  const isFavorited = id ? favoriteIds.includes(id) : false;

  const handleFavoriteClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (!id) return;
    if (!user) {
      router.push('/login');
      return;
    }
    toggleFavorite({ propertyId: id, isFavorited });
  };

  return (
    <motion.div
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: Math.min(index, 12) * 0.05, duration: 0.4 }}
      className="group bg-card rounded-2xl overflow-hidden shadow-card hover:shadow-card-hover transition-all duration-300"
    >
      <Link href={href} className="flex flex-col sm:flex-row">
        {/* Image */}
        <div className="relative sm:w-80 md:w-96 flex-shrink-0 aspect-[4/3] sm:aspect-auto sm:h-auto overflow-hidden">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={image}
            alt={title}
            className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            loading="lazy"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-foreground/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />

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
        <div className="flex-1 p-5 flex flex-col justify-between min-w-0">
          <div>
            <div className="flex items-start justify-between gap-4 mb-2">
              <div>
                <h3 className="text-xl font-display font-bold text-foreground">{price}</h3>
                {secondaryPrice && <span className="text-xs text-muted-foreground">{secondaryPrice}</span>}
              </div>
            </div>
            <p className="text-base font-medium text-foreground/85 mb-1">{title}</p>
            <div
              className={cn(
                "mb-3 flex flex-col gap-2",
                !metroOnSeparateLine && "xl:flex-row xl:items-center xl:gap-3",
              )}
            >
              <p
                className={cn(
                  "text-sm text-muted-foreground flex min-w-0 items-center gap-1.5",
                  !metroOnSeparateLine && "xl:flex-1",
                )}
              >
                <MapPin className="w-3.5 h-3.5 flex-shrink-0" />
                <span className="min-w-0 flex-1 truncate">{address}</span>
              </p>

              {nearbyMetroStations.length > 0 && (
                <div
                  className={cn(
                    "flex flex-wrap items-center gap-2",
                    !metroOnSeparateLine && "xl:flex-nowrap xl:shrink-0",
                  )}
                >
                  {nearbyMetroStations.map((station) => (
                    <span
                      key={station.id}
                      className="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs text-foreground/85"
                    >
                      <TrainFront className="h-3 w-3 text-muted-foreground" />
                      <span className={`h-2 w-2 rounded-full ${lineColorClass(station.line)}`} />
                      {station.name}
                      <span className="text-muted-foreground">{formatDistance(station.distanceKm)}</span>
                    </span>
                  ))}
                </div>
              )}
            </div>

            {description && (
              <p className="text-sm text-muted-foreground line-clamp-2 mb-4">{description}</p>
            )}

            <div className="flex flex-wrap items-center gap-x-5 gap-y-2">
              {beds != null && (
                <span className="flex items-center gap-1.5 text-sm text-foreground/70">
                  <BedDouble className="w-4 h-4" />
                  {beds} комн.
                </span>
              )}
              {baths != null && (
                <span className="flex items-center gap-1.5 text-sm text-foreground/70">
                  <Bath className="w-4 h-4" />
                  {baths} сан.
                </span>
              )}
              <span className="flex items-center gap-1.5 text-sm text-foreground/70">
                <Maximize className="w-4 h-4" />
                {propertyType === 'land'
                  ? (landArea ? `${landArea} сот.` : '-')
                  : `${area} м²`}
              </span>
              {floor && (
                <span className="flex items-center gap-1.5 text-sm text-foreground/70">
                  <Building2 className="w-4 h-4" />
                  {floor}
                </span>
              )}
              {year && (
                <span className="flex items-center gap-1.5 text-sm text-foreground/70">
                  <Calendar className="w-4 h-4" />
                  {year} г.
                </span>
              )}
            </div>
          </div>

          <div className="flex items-center justify-end mt-4 pt-3 border-t border-border">
            <Button variant="ghost" size="sm" className="text-primary hover:text-primary">
              Подробнее <ArrowRight className="w-4 h-4 ml-1" />
            </Button>
          </div>
        </div>
      </Link>
    </motion.div>
  );
};

export default PropertyListCard;
