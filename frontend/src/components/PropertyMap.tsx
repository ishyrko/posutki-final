"use client";

import { useEffect, useRef, useState } from "react";
import { loadYmaps, type YMap, type YPlacemark, type YmapsAPI } from "@/lib/ymaps";
import { buildPropertyUrl } from "@/features/catalog/slugs";

export interface MapProperty {
  id: number;
  lat: number;
  lng: number;
  title: string;
  price: string;
  address: string;
  image: string;
  dealType?: string;
  propertyType?: string;
}

interface PropertyMapProps {
  properties: MapProperty[];
  activeId?: number | null;
  onMarkerClick?: (id: number) => void;
  showBalloons?: boolean;
  regionSlug?: string;
  citySlug?: string;
}

const CITY_MAP_VIEW: Record<string, { center: [number, number]; zoom: number }> = {
  minsk: { center: [53.9045, 27.5615], zoom: 11 },
  brest: { center: [52.0976, 23.7341], zoom: 11 },
  vitebsk: { center: [55.1904, 30.2049], zoom: 11 },
  gomel: { center: [52.4345, 30.9754], zoom: 11 },
  grodno: { center: [53.6694, 23.8131], zoom: 11 },
  mogilev: { center: [53.9006, 30.3319], zoom: 11 },
};

const BELARUS_MAP_VIEW = {
  center: [53.7098, 27.9534] as [number, number],
  zoom: 7,
};

function makeBalloonContent(p: MapProperty): string {
  const href = buildPropertyUrl(p.dealType, p.propertyType, p.id);
  return `<a href="${href}" style="display:block;width:200px;text-decoration:none;color:inherit;">
    <img src="${p.image}" style="width:100%;height:110px;object-fit:cover;border-radius:6px;margin-bottom:6px;" />
    <div style="font-weight:600;font-size:14px;">${p.price}</div>
    <div style="font-size:12px;color:#666;margin-top:2px;">${p.title}</div>
  </a>`;
}

const PropertyMap = ({
  properties,
  activeId,
  onMarkerClick,
  showBalloons = true,
  regionSlug,
  citySlug,
}: PropertyMapProps) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const mapRef = useRef<YMap | null>(null);
  const ymapsRef = useRef<YmapsAPI | null>(null);
  const placemarksRef = useRef<YPlacemark[]>([]);
  const [mapReady, setMapReady] = useState(false);
  const fallbackView = citySlug
    ? (CITY_MAP_VIEW[citySlug] ?? CITY_MAP_VIEW[regionSlug ?? ""] ?? BELARUS_MAP_VIEW)
    : (CITY_MAP_VIEW[regionSlug ?? ""] ?? BELARUS_MAP_VIEW);

  useEffect(() => {
    let cancelled = false;

    loadYmaps().then((ymaps) => {
      if (cancelled || !containerRef.current || mapRef.current) return;

      const map = new ymaps.Map(containerRef.current, {
        center: fallbackView.center,
        zoom: fallbackView.zoom,
        controls: ["zoomControl", "geolocationControl"],
      });

      mapRef.current = map;
      ymapsRef.current = ymaps;
      setMapReady(true);
    });

    return () => {
      cancelled = true;
      if (mapRef.current) {
        mapRef.current.destroy();
        mapRef.current = null;
      }
    };
  }, [fallbackView.center, fallbackView.zoom]);

  useEffect(() => {
    const map = mapRef.current;
    const container = containerRef.current;
    if (!map || !container) return;

    const fitViewport = () => {
      map.container?.fitToViewport();
    };

    // Recalculate map viewport when parent layout changes (e.g. list -> map mode).
    const frameId = requestAnimationFrame(fitViewport);
    let observer: ResizeObserver | undefined;

    if (typeof ResizeObserver !== "undefined") {
      observer = new ResizeObserver(() => fitViewport());
      observer.observe(container);
    }

    window.addEventListener("resize", fitViewport);

    return () => {
      cancelAnimationFrame(frameId);
      observer?.disconnect();
      window.removeEventListener("resize", fitViewport);
    };
  }, [mapReady]);

  useEffect(() => {
    const map = mapRef.current;
    const ymaps = ymapsRef.current;
    if (!map || !ymaps) return;

    placemarksRef.current.forEach((pm) => map.geoObjects.remove(pm));
    placemarksRef.current = [];

    if (properties.length === 0) {
      map.setCenter(fallbackView.center, fallbackView.zoom);
      return;
    }

    properties.forEach((p) => {
      const data = showBalloons
        ? { balloonContentBody: makeBalloonContent(p) }
        : {};

      const placemark = new ymaps.Placemark(
        [p.lat, p.lng],
        data,
        {
          preset:
            p.id === activeId
              ? "islands#redDotIcon"
              : "islands#blueDotIcon",
        }
      );

      if (onMarkerClick) {
        placemark.events.add("click", () => onMarkerClick(p.id));
      }
      map.geoObjects.add(placemark);
      placemarksRef.current.push(placemark);
    });

    if (properties.length === 1) {
      map.setCenter([properties[0].lat, properties[0].lng], 15);
    } else {
      const bounds = map.geoObjects.getBounds();
      if (bounds) {
        map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 40 });
      }
    }
  }, [properties, activeId, onMarkerClick, showBalloons, mapReady, fallbackView.center, fallbackView.zoom]);

  return (
    <div
      ref={containerRef}
      className="w-full h-full rounded-xl"
      style={{ minHeight: "400px" }}
    />
  );
};

export default PropertyMap;
