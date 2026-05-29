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
  regionSlug?: string;
}

interface PropertyMapProps {
  properties: MapProperty[];
  activeId?: number | null;
  onMarkerClick?: (id: number) => void;
  showBalloons?: boolean;
  regionSlug?: string;
  citySlug?: string;
}

interface PlacemarkEntry {
  placemark: YPlacemark;
  propertyIds: number[];
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

function coordKey(lat: number, lng: number): string {
  return `${lat.toFixed(6)},${lng.toFixed(6)}`;
}

function groupPropertiesByLocation(properties: MapProperty[]): MapProperty[][] {
  const groups = new Map<string, MapProperty[]>();
  for (const p of properties) {
    const key = coordKey(p.lat, p.lng);
    const list = groups.get(key) ?? [];
    list.push(p);
    groups.set(key, list);
  }
  return [...groups.values()];
}

function placemarkPreset(isActive: boolean, count: number): string {
  if (count > 1) {
    return isActive ? "islands#redCircleDotIconWithCaption" : "islands#blueCircleDotIconWithCaption";
  }
  return isActive ? "islands#redDotIcon" : "islands#blueDotIcon";
}

function makeBalloonContent(p: MapProperty): string {
  const href = buildPropertyUrl(p.propertyType, p.id, p.regionSlug);
  return `<a href="${href}" style="display:block;width:200px;text-decoration:none;color:inherit;">
    <img src="${p.image}" style="width:100%;height:110px;object-fit:cover;border-radius:6px;margin-bottom:6px;" />
    <div style="font-weight:600;font-size:14px;">${p.price}</div>
    <div style="font-size:12px;color:#666;margin-top:2px;">${p.title}</div>
  </a>`;
}

function makeGroupBalloonContent(group: MapProperty[]): string {
  const items = group
    .map((p) => {
      const href = buildPropertyUrl(p.propertyType, p.id, p.regionSlug);
      return `<a href="${href}" style="display:flex;gap:10px;padding:8px 0;text-decoration:none;color:inherit;border-bottom:1px solid #eee;">
      <img src="${p.image}" style="width:72px;height:54px;object-fit:cover;border-radius:6px;flex-shrink:0;" />
      <div style="min-width:0;flex:1;">
        <div style="font-weight:600;font-size:14px;">${p.price}</div>
        <div style="font-size:12px;color:#666;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${p.title}</div>
      </div>
    </a>`;
    })
    .join("");
  return `<div style="width:240px;max-height:320px;overflow-y:auto;">${items}</div>`;
}

function pickClickId(group: MapProperty[], activeId: number | null | undefined): number {
  if (activeId != null && group.some((p) => p.id === activeId)) {
    return activeId;
  }
  return group[0].id;
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
  const placemarkEntriesRef = useRef<PlacemarkEntry[]>([]);
  const onMarkerClickRef = useRef(onMarkerClick);
  const activeIdRef = useRef(activeId);
  const [mapReady, setMapReady] = useState(false);
  const fallbackView = citySlug
    ? (CITY_MAP_VIEW[citySlug] ?? CITY_MAP_VIEW[regionSlug ?? ""] ?? BELARUS_MAP_VIEW)
    : (CITY_MAP_VIEW[regionSlug ?? ""] ?? BELARUS_MAP_VIEW);

  onMarkerClickRef.current = onMarkerClick;
  activeIdRef.current = activeId;

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
      placemarkEntriesRef.current = [];
    };
  }, [fallbackView.center, fallbackView.zoom]);

  useEffect(() => {
    const map = mapRef.current;
    const container = containerRef.current;
    if (!map || !container) return;

    const fitViewport = () => {
      map.container?.fitToViewport();
    };

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
    placemarkEntriesRef.current.forEach(({ placemark, propertyIds }) => {
      const isActive = activeId != null && propertyIds.includes(activeId);
      const count = propertyIds.length;
      placemark.options.set("preset", placemarkPreset(isActive, count));
      if (count > 1) {
        placemark.options.set("iconCaption", String(count));
      }
    });
  }, [activeId]);

  useEffect(() => {
    const map = mapRef.current;
    const ymaps = ymapsRef.current;
    if (!map || !ymaps) return;

    placemarkEntriesRef.current.forEach(({ placemark }) => map.geoObjects.remove(placemark));
    placemarkEntriesRef.current = [];

    if (properties.length === 0) {
      map.setCenter(fallbackView.center, fallbackView.zoom);
      return;
    }

    const groups = groupPropertiesByLocation(properties);

    groups.forEach((group) => {
      const [lat, lng] = [group[0].lat, group[0].lng];
      const isActive = activeId != null && group.some((p) => p.id === activeId);
      const count = group.length;

      const data = showBalloons
        ? {
            balloonContentBody:
              count === 1 ? makeBalloonContent(group[0]) : makeGroupBalloonContent(group),
            ...(count > 1
              ? { balloonContentHeader: `${count} объявления в этом месте` }
              : {}),
          }
        : {};

      const options: Record<string, string | boolean> = {
        preset: placemarkPreset(isActive, count),
      };
      if (count > 1) {
        options.iconCaption = String(count);
      }

      const placemark = new ymaps.Placemark([lat, lng], data, options);

      placemark.events.add("click", () => {
        const id = pickClickId(group, activeIdRef.current);
        onMarkerClickRef.current?.(id);
        if (showBalloons) {
          placemark.balloon.open();
        }
      });

      map.geoObjects.add(placemark);
      placemarkEntriesRef.current.push({
        placemark,
        propertyIds: group.map((p) => p.id),
      });
    });

    if (properties.length === 1) {
      map.setCenter([properties[0].lat, properties[0].lng], 15);
    } else {
      const bounds = map.geoObjects.getBounds();
      if (bounds) {
        map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 40 });
      }
    }
  }, [properties, showBalloons, mapReady, fallbackView.center, fallbackView.zoom]);

  return (
    <div
      ref={containerRef}
      className="w-full h-full rounded-xl"
      style={{ minHeight: "400px" }}
    />
  );
};

export default PropertyMap;
