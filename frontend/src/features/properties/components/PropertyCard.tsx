"use client";

import { useMemo } from "react";
import { motion } from "framer-motion";
import { MapPin, BedDouble, Bath, Maximize, Heart } from "lucide-react";
import Link from "next/link";
import type { ExchangeRates } from "../api";
import { Property, formatAddress } from "../types";
import { DEFAULT_EXCHANGE_RATES_FALLBACK, formatPropertyPrices } from "../price-display";
import { useToggleFavorite, useFavoriteIds, useExchangeRates } from "../hooks";
import { useUser } from "@/features/auth/hooks";
import { useRouter } from "next/navigation";
import { buildPropertyUrl } from "@/features/catalog/slugs";
import { showBathrooms, showRooms } from "@/features/create-listing/property-field-rules";

const PROPERTY_TYPE_LABELS: Record<string, string> = {
  apartment: "Квартира",
  house: "Дом",
  room: "Комната",
  land: "Участок",
  garage: "Гараж",
  parking: "Машиноместо",
  dacha: "Дача",
  office: "Офис",
  retail: "Торговое помещение",
  warehouse: "Склад",
  business: "Готовый бизнес",
};

interface PropertyCardProps {
  property: Property;
  index?: number;
}

export const PropertyCard = ({ property, index = 0 }: PropertyCardProps) => {
  const {
    id,
    title,
    dealType,
    type,
    address,
    specifications,
    images
  } = property;

  const coverImage = images && images.length > 0
    ? (images[0].thumbnailUrl || images[0].url)
    : "/placeholder.svg";
  const { data: user } = useUser();
  const { data: favoriteIds = [] } = useFavoriteIds();
  const { mutate: toggleFavorite } = useToggleFavorite();
  const router = useRouter();
  const isFavorited = favoriteIds.includes(id);
  const href = buildPropertyUrl(dealType, type, id);

  const { data: rates } = useExchangeRates();
  const exchangeRates: ExchangeRates = useMemo(
    () => rates ?? DEFAULT_EXCHANGE_RATES_FALLBACK,
    [rates],
  );
  const { primary: pricePrimary, secondary: priceSecondary } = formatPropertyPrices(property, exchangeRates);

  const handleFavoriteClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (!user) {
      router.push('/login');
      return;
    }
    toggleFavorite({ propertyId: id, isFavorited });
  };

  return (
    <Link href={href}>
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5, delay: index * 0.1 }}
        className="group bg-card rounded-2xl overflow-hidden border border-border/50 hover:border-primary/20 hover:shadow-card-hover transition-all duration-500"
      >
        {/* Image Container */}
        <div className="relative aspect-[4/3] overflow-hidden">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={coverImage}
            alt={title}
            className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
          />

          {/* Badges */}
          <div className="absolute top-4 left-4 flex gap-2">
            <span className="px-3 py-1 rounded-full bg-dark-bg/60 backdrop-blur-md text-white text-[10px] font-bold uppercase tracking-wider border border-white/10">
              {dealType === 'sale' ? 'Продажа' : dealType === 'daily' ? 'Посуточно' : 'Аренда'}
            </span>
            <span className="px-3 py-1 rounded-full bg-primary text-white text-[10px] font-bold uppercase tracking-wider shadow-primary">
              {property.typeLabel ?? PROPERTY_TYPE_LABELS[type] ?? type}
            </span>
          </div>

          {/* Favorite Button */}
          <button
            type="button"
            onClick={handleFavoriteClick}
            className={`absolute top-4 right-4 p-2 rounded-full backdrop-blur-md border transition-all duration-300 ${
              isFavorited
                ? 'bg-primary border-primary text-white'
                : 'bg-white/10 border-white/20 text-white hover:bg-primary hover:border-primary'
            }`}
          >
            <Heart className={`w-4 h-4 ${isFavorited ? 'fill-current' : ''}`} />
          </button>

          {/* Price Tag */}
          <div className="absolute bottom-4 left-4">
            <div className="px-4 py-2 rounded-xl bg-dark-bg/80 backdrop-blur-md border border-white/10 text-white">
              <span className="text-lg font-bold font-display">
                {pricePrimary}
              </span>
              {dealType === 'rent' && <span className="text-sm opacity-60 font-normal"> /мес</span>}
              <span className="block text-xs font-normal opacity-85 mt-0.5">{priceSecondary}</span>
            </div>
          </div>
        </div>

        {/* Content */}
        <div className="p-5">
          <div className="flex justify-between items-start mb-2">
            <div className="space-y-1">
              <h3 className="text-lg font-bold text-foreground group-hover:text-primary transition-colors line-clamp-1">
                {title}
              </h3>
              <div className="flex items-center gap-1.5 text-sm text-muted-foreground">
                <MapPin className="w-3.5 h-3.5" />
                <span className="line-clamp-1">{formatAddress(address)}</span>
              </div>
            </div>
          </div>

          {/* Features */}
          <div className="flex items-start gap-4 pt-4 mt-4 border-t border-border/50">
            {showRooms(type) && specifications.rooms != null && (
              <div className="flex flex-col gap-1 min-w-0">
                <div className="flex items-center gap-1.5 text-muted-foreground">
                  <BedDouble className="w-4 h-4" />
                  <span className="text-[10px] font-bold uppercase tracking-wider opacity-60 px-0.5">Комнаты</span>
                </div>
                <span className="text-sm font-bold ml-1">{specifications.rooms}</span>
              </div>
            )}
            {showBathrooms(type) && specifications.bathrooms != null && (
              <div className="flex flex-col gap-1 min-w-0">
                <div className="flex items-center gap-1.5 text-muted-foreground">
                  <Bath className="w-4 h-4" />
                  <span className="text-[10px] font-bold uppercase tracking-wider opacity-60 px-0.5">Санузлы</span>
                </div>
                <span className="text-sm font-bold ml-1">{specifications.bathrooms}</span>
              </div>
            )}
            <div className="flex flex-col gap-1 min-w-0">
              <div className="flex items-center gap-1.5 text-muted-foreground">
                <Maximize className="w-4 h-4" />
                <span className="text-[10px] font-bold uppercase tracking-wider opacity-60 px-0.5">
                  {type === 'land' ? 'Участок' : 'Площадь общая'}
                </span>
              </div>
              <span className="text-sm font-bold ml-1">
                {type === 'land'
                  ? (specifications.landArea ? `${specifications.landArea} сот.` : '-')
                  : `${specifications.area} м²`}
              </span>
            </div>
          </div>
        </div>
      </motion.div>
    </Link>
  );
};
