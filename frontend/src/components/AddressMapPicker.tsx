"use client";

import { useEffect, useRef, useCallback } from "react";
import { loadYmaps, type YMap, type YPlacemark, type YmapsAPI } from "@/lib/ymaps";

interface AddressMapPickerProps {
  center: [number, number];
  latitude: number | null;
  longitude: number | null;
  onCoordsChange: (lat: number, lng: number) => void;
}

export default function AddressMapPicker({
  center,
  latitude,
  longitude,
  onCoordsChange,
}: AddressMapPickerProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const mapRef = useRef<YMap | null>(null);
  const ymapsRef = useRef<YmapsAPI | null>(null);
  const markerRef = useRef<YPlacemark | null>(null);
  const onCoordsChangeRef = useRef(onCoordsChange);
  const initPropsRef = useRef({ center, latitude, longitude });

  useEffect(() => {
    onCoordsChangeRef.current = onCoordsChange;
  }, [onCoordsChange]);

  useEffect(() => {
    initPropsRef.current = { center, latitude, longitude };
  }, [center, latitude, longitude]);

  const placeMarker = useCallback((coords: [number, number]) => {
    const map = mapRef.current;
    const ymaps = ymapsRef.current;
    if (!map || !ymaps) return;

    if (markerRef.current) {
      map.geoObjects.remove(markerRef.current);
    }

    const placemark = new ymaps.Placemark(coords, {}, {
      preset: "islands#redDotIcon",
      draggable: "true",
    });

    placemark.events.add("dragend", () => {
      const pos = placemark.geometry.getCoordinates();
      onCoordsChangeRef.current(pos[0], pos[1]);
    });

    map.geoObjects.add(placemark);
    markerRef.current = placemark;
  }, []);

  useEffect(() => {
    let cancelled = false;

    loadYmaps().then((ymaps) => {
      if (cancelled || !containerRef.current || mapRef.current) return;

      const { center: c, latitude: lat, longitude: lng } = initPropsRef.current;
      const hasCoords = lat !== null && lng !== null;
      const mapCenter: [number, number] = hasCoords ? [lat, lng] : c;

      const map = new ymaps.Map(containerRef.current, {
        center: mapCenter,
        zoom: hasCoords ? 16 : 11,
        controls: ["zoomControl", "geolocationControl"],
      });

      map.events.add("click", (e) => {
        const coords = e.get("coords") as unknown as number[];
        placeMarker([coords[0], coords[1]]);
        onCoordsChangeRef.current(coords[0], coords[1]);
      });

      mapRef.current = map;
      ymapsRef.current = ymaps;

      if (hasCoords) {
        placeMarker([lat, lng]);
      }
    });

    return () => {
      cancelled = true;
      if (mapRef.current) {
        mapRef.current.destroy();
        mapRef.current = null;
      }
    };
  }, [placeMarker]);

  useEffect(() => {
    const map = mapRef.current;
    if (!map || latitude === null || longitude === null) return;
    placeMarker([latitude, longitude]);
    map.setCenter([latitude, longitude], 16);
  }, [latitude, longitude, placeMarker]);

  useEffect(() => {
    const map = mapRef.current;
    if (!map || latitude !== null) return;
    map.setCenter(center, 11);
  }, [center, latitude]);

  return (
    <div className="space-y-2">
      <div
        ref={containerRef}
        className="w-full rounded-xl overflow-hidden"
        style={{ height: "300px" }}
      />
      <p className="text-xs text-muted-foreground">
        Нажмите на карту, чтобы указать точное расположение, или перетащите
        маркер
      </p>
    </div>
  );
}
