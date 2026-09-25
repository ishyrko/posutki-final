#!/usr/bin/env python3
"""One-off script: export Belarus city boundaries from OSM to city-boundaries.json."""
from __future__ import annotations

import json
import sys
import time
import urllib.parse
import urllib.request
from collections import defaultdict
from datetime import date
from pathlib import Path

OVERPASS_URL = "https://overpass-api.de/api/interpreter"
NOMINATIM_URL = "https://nominatim.openstreetmap.org/lookup"
USER_AGENT = "agroturizm-by/1.0 (city-boundaries-export)"
THRESHOLD = "0.001"
NOMINATIM_DELAY = 1.1


def norm(s: str) -> str:
    return s.replace("ё", "е").replace("Ё", "Е").strip()


def fetch_overpass() -> list[dict]:
    query = (
        '[out:json][timeout:180];'
        'area["ISO3166-1"="BY"][admin_level=2]->.a;'
        'rel(area.a)["boundary"="administrative"]["place"~"^(city|town)$"];'
        "out tags;"
    )
    req = urllib.request.Request(
        OVERPASS_URL,
        data=urllib.parse.urlencode({"data": query}).encode(),
        headers={"User-Agent": USER_AGENT},
    )
    with urllib.request.urlopen(req, timeout=200) as resp:
        data = json.load(resp)
    return data.get("elements", [])


def fetch_polygon(osm_id: int) -> dict | None:
    params = urllib.parse.urlencode(
        {
            "osm_ids": f"R{osm_id}",
            "format": "json",
            "polygon_geojson": "1",
            "polygon_threshold": THRESHOLD,
        }
    )
    req = urllib.request.Request(
        f"{NOMINATIM_URL}?{params}",
        headers={"User-Agent": USER_AGENT},
    )
    with urllib.request.urlopen(req, timeout=60) as resp:
        rows = json.load(resp)
    if not rows:
        return None
    row = rows[0]
    geojson = row.get("geojson")
    if not geojson:
        return None
    bbox = [float(x) for x in row["boundingbox"]]
    # Nominatim bbox: [minLat, maxLat, minLon, maxLon]
    bbox_out = [bbox[0], bbox[2], bbox[1], bbox[3]]

    rings: list[list[list[float]]] = []
    if geojson["type"] == "Polygon":
        rings = [[[pt[1], pt[0]] for pt in ring] for ring in geojson["coordinates"]]
    elif geojson["type"] == "MultiPolygon":
        for poly in geojson["coordinates"]:
            rings.extend([[[pt[1], pt[0]] for pt in ring] for ring in poly])
    else:
        return None

    return {"bbox": bbox_out, "rings": rings}


def pick_relation(
    candidates: list[tuple[int, dict]],
    region_id: int,
    region_centroids: dict[int, tuple[float, float]],
) -> int:
    """Pick best OSM relation id for a city."""
    if len(candidates) == 1:
        return candidates[0][0]

    def admin_level(rel_id: int, tags: dict) -> int:
        return int(tags.get("admin_level", "99"))

    sorted_cands = sorted(candidates, key=lambda c: admin_level(c[0], c[1]))
    min_level = admin_level(sorted_cands[0][0], sorted_cands[0][1])
    same_level = [c for c in sorted_cands if admin_level(c[0], c[1]) == min_level]

    if len(same_level) == 1:
        return same_level[0][0]

    # Disambiguate Свислочь: prefer relation whose centroid is in the same region bbox area
    # Use region centroid distance as heuristic
    reg_lat, reg_lon = region_centroids.get(region_id, (53.9, 27.5))

    def score(rel_id: int, tags: dict) -> float:
        # fetch quick centroid from nominatim bbox center - we don't have it yet
        # use admin_level only for now; for Свислочь manual override below
        return admin_level(rel_id, tags)

    # Manual overrides for known ambiguous names
    names = {norm(c[1].get("name:ru") or c[1].get("name", "")) for c in same_level}
    if "свислочь" in names and region_id == 5:  # Minsk region -> Pukhovichi gp
        for rel_id, tags in same_level:
            n = norm(tags.get("name:ru") or tags.get("name", ""))
            if n == "свислочь" and tags.get("place") == "town":
                return rel_id
    if "свислочь" in names and region_id == 4:  # Grodno region -> city
        for rel_id, tags in same_level:
            n = norm(tags.get("name:ru") or tags.get("name", ""))
            if n == "свислочь":
                return rel_id

    return same_level[0][0]


def main() -> int:
    root = Path(__file__).resolve().parents[1]
    tsv_path = Path("/tmp/cities_for_boundaries.tsv")
    if not tsv_path.exists():
        print("Run mysql export first", file=sys.stderr)
        return 1

    cities: list[dict] = []
    for line in tsv_path.read_text(encoding="utf-8").splitlines():
        parts = line.split("\t")
        if len(parts) < 6:
            continue
        cities.append(
            {
                "slug": parts[0],
                "lookup_name": parts[1],
                "name": parts[2],
                "district_id": int(parts[3]),
                "region_id": int(parts[4]),
                "region_name": parts[5],
            }
        )

    if len(cities) != 115:
        print(f"Expected 115 cities, got {len(cities)}", file=sys.stderr)
        return 1

    print("Fetching Overpass boundaries...")
    elements = fetch_overpass()
    by_name: dict[str, list[tuple[int, dict]]] = defaultdict(list)
    for el in elements:
        tags = el.get("tags", {})
        name = tags.get("name:ru") or tags.get("name")
        if name:
            by_name[norm(name)].append((el["id"], tags))

    region_centroids = {
        1: (52.1, 23.7),   # Brest
        2: (55.2, 30.2),   # Vitebsk
        3: (52.4, 31.0),   # Gomel
        4: (53.7, 25.3),   # Grodno
        5: (53.9, 27.5),   # Minsk
        7: (53.9, 30.3),   # Mogilev
    }

    output: dict[str, dict] = {}
    missing: list[str] = []

    for i, city in enumerate(cities):
        lookup = norm(city["lookup_name"])
        cands = by_name.get(lookup, [])
        if not cands:
            missing.append(city["slug"])
            continue

        rel_id = pick_relation(cands, city["region_id"], region_centroids)
        print(f"[{i + 1}/115] {city['slug']} -> R{rel_id}")
        time.sleep(NOMINATIM_DELAY)
        poly = fetch_polygon(rel_id)
        if poly is None:
            missing.append(city["slug"])
            continue
        output[city["slug"]] = poly

    if missing:
        print("Missing polygons for:", missing, file=sys.stderr)
        return 1

    out_path = root / "resources/geo/city-boundaries.json"
    out_path.parent.mkdir(parents=True, exist_ok=True)
    payload = {
        "_meta": {
            "source": "OpenStreetMap via Overpass + Nominatim",
            "license": "ODbL",
            "exported_at": date.today().isoformat(),
            "polygon_threshold": THRESHOLD,
            "city_count": len(output),
        },
        "cities": output,
    }
    out_path.write_text(json.dumps(payload, ensure_ascii=False, separators=(",", ":")), encoding="utf-8")
    print(f"Wrote {out_path} ({out_path.stat().st_size} bytes, {len(output)} cities)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
