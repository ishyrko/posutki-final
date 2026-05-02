const YMAPS_SRC = `https://api-maps.yandex.ru/2.1/?apikey=${process.env.NEXT_PUBLIC_YANDEX_MAPS_API_KEY}&lang=ru_RU`;

export interface YMap {
  container?: {
    fitToViewport: () => void;
  };
  geoObjects: {
    add: (obj: YPlacemark) => void;
    remove: (obj: YPlacemark) => void;
    getBounds: () => number[][] | null;
  };
  setBounds: (
    bounds: number[][],
    options: { checkZoomRange: boolean; zoomMargin: number }
  ) => void;
  setCenter: (center: number[], zoom: number) => void;
  destroy: () => void;
  events: { add: (event: string, handler: (e: YMapEvent) => void) => void };
}

export interface YPlacemark {
  events: { add: (event: string, handler: (e: YMapEvent) => void) => void };
  options: { set: (key: string, value: string | boolean) => void };
  geometry: { getCoordinates: () => number[] };
}

export interface YMapEvent {
  get: (key: string) => number[];
}

export type YmapsAPI = typeof window.ymaps;

declare global {
  interface Window {
    ymaps: {
      ready: () => Promise<void>;
      Map: new (
        container: HTMLElement,
        state: { center: number[]; zoom: number; controls?: string[] }
      ) => YMap;
      Placemark: new (
        coords: number[],
        properties: Record<string, string | undefined>,
        options: Record<string, string | boolean>
      ) => YPlacemark;
      geocode: (query: string, options?: { results?: number }) => Promise<{
        geoObjects: { get: (i: number) => { geometry: { getCoordinates: () => number[] } } | null };
      }>;
    };
  }
}

export function loadYmaps(): Promise<YmapsAPI> {
  if (window.ymaps) return window.ymaps.ready().then(() => window.ymaps);

  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${YMAPS_SRC}"]`)) {
      const check = setInterval(() => {
        if (window.ymaps) {
          clearInterval(check);
          window.ymaps.ready().then(() => resolve(window.ymaps));
        }
      }, 50);
      return;
    }
    const script = document.createElement("script");
    script.src = YMAPS_SRC;
    script.onload = () => {
      window.ymaps.ready().then(() => resolve(window.ymaps));
    };
    script.onerror = reject;
    document.head.appendChild(script);
  });
}
