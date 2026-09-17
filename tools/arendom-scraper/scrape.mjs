#!/usr/bin/env node
/**
 * Scrapes arendom.com through a real browser (Imunify360 JS challenge)
 * into listings.json + images/ for app:import-partner-listings.
 *
 * Listing index comes from WP REST CPT `kvartiry` (homepage has no catalog links).
 * Details and photos are taken from the public card HTML + attached media.
 */
import { execFile } from 'node:child_process';
import { createHash } from 'node:crypto';
import { promisify } from 'node:util';
import { chromium } from 'playwright';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const START_URL = process.env.START_URL || 'https://arendom.com/';
const API_URL = process.env.API_URL || 'https://arendom.com/wp-json/wp/v2/kvartiry';
const OUTPUT_DIR = process.env.OUTPUT_DIR || '/data';
const DELAY_MS = Number.parseInt(process.env.DELAY_MS || '800', 10);
const CHROME_UA =
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const execFileAsync = promisify(execFile);

const skipPathParts = [
  '/wp-',
  '/cart',
  '/checkout',
  '/account',
  '/privacy',
  '/politika',
  '/region/',
  '/kvartiry/page/',
];

/** Theme chrome, not listing photos (header Instagram icon is 1684702232_instagramm.png). */
const skipImagePath = /logo|icon|sprite|watermark|favicon|instagramm?|whatsapp|viber|telegram|facebook/i;

/** MD5 of arendom.com header Instagram PNG attached/scraped on every card. */
const skipImageMd5 = new Set(['75bf81195ba4f2b5281194c56001e931']);

const amenityLabels = [
  'Wi-Fi',
  'Wi-fi',
  'холодильник',
  'стиральная машина',
  'телевизор',
  'кондиционер',
  'микроволновка',
  'СВЧ',
  'фен',
  'утюг',
  'парковка',
  'посуда',
  'полотенца',
  'плита',
  'электрочайник',
  'мебель',
];

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function parseLimitFromArgv() {
  const eq = process.argv.find((arg) => arg.startsWith('--limit='));
  if (eq) {
    return Number.parseInt(eq.slice(8), 10) || 0;
  }
  const idx = process.argv.indexOf('--limit');
  if (idx >= 0 && process.argv[idx + 1]) {
    return Number.parseInt(process.argv[idx + 1], 10) || 0;
  }
  return Number.parseInt(process.env.LIMIT || '0', 10) || 0;
}

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true });
}

async function loadState(outFile) {
  try {
    const raw = await fs.readFile(outFile, 'utf8');
    const parsed = JSON.parse(raw);
    if (parsed && Array.isArray(parsed.listings)) {
      parsed.listings = parsed.listings.filter(
        (item) => item && typeof item.externalId === 'string' && !item.externalId.startsWith('example-'),
      );
      return parsed;
    }
  } catch {
    // first run
  }
  return { source: 'arendom', listings: [] };
}

async function waitForSite(page) {
  await page.goto(START_URL, { waitUntil: 'domcontentloaded', timeout: 90_000 });
  await page.waitForFunction(
    () => {
      const text = `${document.title} ${document.body?.innerText || ''}`;
      const challenged =
        /one moment/i.test(text) ||
        /imunify/i.test(text) ||
        /checking your browser/i.test(text);
      const ready = /арендом|посуточн|квартир/i.test(text);
      return !challenged && ready;
    },
    null,
    { timeout: 90_000 },
  );
  await page.waitForTimeout(800);
  console.log('Passed challenge:', page.url(), await page.title());
}

function decode(value) {
  return String(value ?? '')
    .replace(/&nbsp;/g, ' ')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&amp;/g, '&')
    .replace(/&#(\d+);/g, (_, code) => String.fromCharCode(Number(code)))
    .replace(/<\/?[^>]+>/g, '')
    .replace(/\s+/g, ' ')
    .trim();
}

function firstMatch(input, regex) {
  const match = input.match(regex);
  return match ? decode(match[1]) : null;
}

function toAbsolute(href, base) {
  try {
    return new URL(href, base).href;
  } catch {
    return null;
  }
}

function hasEnoughPhotos(item) {
  return Array.isArray(item?.images) && item.images.length >= 3;
}

function hasCoordinates(item) {
  const latitude = Number(item?.latitude);
  const longitude = Number(item?.longitude);
  return Number.isFinite(latitude) && Number.isFinite(longitude) && latitude !== 0 && longitude !== 0;
}

function looksLikeBelarus(latitude, longitude) {
  return latitude >= 51 && latitude <= 56.5 && longitude >= 23 && longitude <= 33;
}

function isYandexMapsUrl(href) {
  if (!href) {
    return false;
  }
  try {
    const url = new URL(href, 'https://yandex.by');
    const host = url.hostname.replace(/^www\./, '');
    const isYandexHost = /(^|\.)yandex\.(by|ru|com)$/i.test(host) || /^maps\.yandex\./i.test(host);
    if (!isYandexHost || /^(mc|metrika)\.yandex\./i.test(host)) {
      return false;
    }
    return /\/maps(\/|$)/.test(url.pathname) || host.startsWith('maps.yandex');
  } catch {
    return false;
  }
}

function collectYandexMapsUrls(html) {
  const urls = [];
  for (const match of html.matchAll(/href=["']([^"']+)["']/gi)) {
    const href = decode(match[1]);
    if (isYandexMapsUrl(href)) {
      urls.push(href);
    }
  }
  return [...new Set(urls)];
}

function parseLonLatPair(value) {
  if (!value) {
    return null;
  }
  const parts = decodeURIComponent(String(value)).split(',');
  if (parts.length < 2) {
    return null;
  }
  const first = Number.parseFloat(parts[0]);
  const second = Number.parseFloat(parts[1]);
  if (!Number.isFinite(first) || !Number.isFinite(second)) {
    return null;
  }
  // Yandex `ll` / `pt` / `sll` are lon,lat.
  if (looksLikeBelarus(second, first)) {
    return { latitude: second, longitude: first };
  }
  if (looksLikeBelarus(first, second)) {
    return { latitude: first, longitude: second };
  }
  return null;
}

function parseCoordinatesFromMapsUrl(rawUrl) {
  let url;
  try {
    url = new URL(rawUrl, 'https://yandex.by');
  } catch {
    return null;
  }
  const params = new URLSearchParams(url.search);
  if (url.hash.includes('=')) {
    const hashQuery = url.hash.replace(/^#/, '');
    const hashParams = new URLSearchParams(hashQuery.startsWith('?') ? hashQuery.slice(1) : hashQuery);
    for (const [key, value] of hashParams) {
      if (!params.has(key)) {
        params.set(key, value);
      }
    }
  }
  for (const key of ['ll', 'pt', 'sll']) {
    const parsed = parseLonLatPair(params.get(key));
    if (parsed) {
      return parsed;
    }
  }
  return parseLonLatPair(params.get('whatshere[point]'));
}

async function readRedirectLocationWithCurl(url) {
  const { stdout } = await execFileAsync(
    'curl',
    ['-sI', '-A', CHROME_UA, '--max-time', '8', url],
    { timeout: 10_000 },
  );
  const match = String(stdout).match(/^location:\s*(.+)$/im);
  const location = match ? match[1].trim() : '';
  if (!location || /showcaptcha/i.test(location)) {
    return null;
  }
  return location;
}

async function readFinalMapsUrlWithBrowser(page, url) {
  const mapsPage = await page.context().newPage();
  try {
    await mapsPage.goto(url, { waitUntil: 'commit', timeout: 8_000 });
    return mapsPage.url();
  } finally {
    await mapsPage.close();
  }
}

async function resolveYandexMapsCoordinates(url, cache, page) {
  if (cache.has(url)) {
    return cache.get(url);
  }

  let coords = parseCoordinatesFromMapsUrl(url);

  if (!coords) {
    try {
      const location = await readRedirectLocationWithCurl(url);
      if (location) {
        coords = parseCoordinatesFromMapsUrl(new URL(location, url).href);
      }
    } catch {
      // Docker/host curl fingerprints differ; browser hop is the fallback.
    }
  }

  if (!coords && page) {
    try {
      const finalUrl = await readFinalMapsUrlWithBrowser(page, url);
      coords = parseCoordinatesFromMapsUrl(finalUrl);
    } catch {
      coords = null;
    }
  }

  cache.set(url, coords ?? null);
  return coords ?? null;
}

async function resolveListingCoordinates(html, cache, page) {
  for (const url of collectYandexMapsUrls(html)) {
    const coords = await resolveYandexMapsCoordinates(url, cache, page);
    if (coords) {
      return coords;
    }
  }
  return null;
}

function parseAddress(title) {
  const cleaned = decode(title);
  const withBuilding = cleaned.match(/^(.+?),\s*(.+?),\s*д\.?\s*(.+)$/i);
  if (withBuilding) {
    return {
      cityName: withBuilding[1].trim(),
      streetName: withBuilding[2].replace(/^(ул\.|улица|пр\.|проспект)\s+/i, '').trim(),
      building: withBuilding[3].trim(),
    };
  }
  const parts = cleaned.split(',').map((part) => part.trim()).filter(Boolean);
  return {
    cityName: parts[0] || '',
    streetName: (parts[1] || '').replace(/^(ул\.|улица|пр\.|проспект)\s+/i, ''),
    building: (parts[2] || '').replace(/^д\.?\s*/i, ''),
  };
}

function isListingPhotoUrl(src) {
  if (!src || src.startsWith('data:')) {
    return false;
  }
  if (!/wp-content\/uploads/i.test(src)) {
    return false;
  }
  if (skipImagePath.test(src)) {
    return false;
  }
  if (/\/cropped-/i.test(src) || /-\d{2,4}x\d{2,4}\.(jpe?g|png|webp)(?:\?|$)/i.test(src)) {
    return false;
  }
  return true;
}

function collectPageImageUrls(html, url) {
  const fromLightbox = [...html.matchAll(/data-pswp-src=["']([^"']+)["']/gi)]
    .map((match) => toAbsolute(match[1], url))
    .filter(isListingPhotoUrl);
  if (fromLightbox.length >= 3) {
    return [...new Set(fromLightbox)];
  }
  const fromTags = [...html.matchAll(/<(?:img|source)[^>]+(?:src|data-src|data-lazy-src)=["']([^"']+)["']/gi)]
    .map((match) => toAbsolute(match[1], url))
    .filter(isListingPhotoUrl);
  return [...new Set([...fromLightbox, ...fromTags])];
}

function parseBathroom(value) {
  if (/раздельн/i.test(value)) {
    return { bathrooms: 1, bathroomType: 'separate', amenity: 'раздельный санузел' };
  }
  if (/совмещ/i.test(value)) {
    return { bathrooms: 1, bathroomType: 'combined', amenity: 'совмещенный санузел' };
  }
  const countMatch = value.match(/(\d+)/);
  const count = countMatch ? Number.parseInt(countMatch[1], 10) : 0;
  if (count >= 2) {
    return { bathrooms: count, bathroomType: 'two', amenity: null };
  }
  return { bathrooms: 1, bathroomType: 'combined', amenity: 'совмещенный санузел' };
}

function looksLikeListingUrl(url) {
  if (!url || !url.startsWith('https://arendom.com/')) {
    return false;
  }
  if (url.includes('#') || url.includes('mailto:') || url.includes('tel:')) {
    return false;
  }
  if (skipPathParts.some((part) => url.includes(part))) {
    return false;
  }
  return /\/kvartiry\/[^/]+\/?$/.test(url) || /\/\d+\/?$/.test(url);
}

function extractListing(url, html, text, meta = {}) {
  const title =
    decode(meta.title) ||
    firstMatch(html, /<h1[^>]*>([\s\S]*?)<\/h1>/i) ||
    firstMatch(html, /<meta[^>]+property="og:title"[^>]+content="([^"]+)"/i) ||
    '';
  const description =
    decode(meta.description) ||
    firstMatch(html, /<meta[^>]+name="description"[^>]+content="([^"]+)"/i) ||
    firstMatch(html, /<meta[^>]+property="og:description"[^>]+content="([^"]+)"/i) ||
    '';
  const priceMatch =
    text.match(/цена за сутки[:\s]*(\d[\d\s]{0,6})\s*(?:руб|р\.|BYN)/i) ||
    text.match(/(\d[\d\s]{0,6})\s*BYN\s*сутки/i) ||
    text.match(/(\d[\d\s]{1,6})\s*(?:р\.|руб|BYN)/i);
  const priceByn = priceMatch ? Number.parseInt(priceMatch[1].replace(/\s+/g, ''), 10) : 0;
  const roomsMatch =
    text.match(/(\d)\s*[-–]?\s*комнатн/i) ||
    title.match(/(\d)\s*[-–]?\s*комнатн/i) ||
    text.match(/(\d)\s*[-–]?\s*комн/i);
  const areaMatch = text.match(/(\d+[.,]?\d*)\s*м\s*[²2]/i);
  const stackedFloor = text.match(/(\d+)\s*\/\s*(\d+)\s*этаж/i);
  const simpleFloor = text.match(/\/\s*(\d+)\s*этаж/i) || text.match(/(\d+)\s*этаж/i);
  const guestsMatch =
    text.match(/до\s*(\d+)\s*гост/i) ||
    text.match(/(\d+)\s*максимум гост/i) ||
    text.match(/максимум гост[^\d]{0,12}(\d+)/i);
  const bathroomsMatch = text.match(/санузел[:\s]+([^\n]+)/i);
  const bathroomValue = bathroomsMatch ? bathroomsMatch[1].trim() : '';
  const bathroom = parseBathroom(bathroomValue);
  const address = parseAddress(title);
  const cityName = decode(meta.cityName) || address.cityName;

  const uniqueImages = [...new Set([
    ...(meta.imageUrls || []).filter(isListingPhotoUrl),
    ...collectPageImageUrls(html, url),
  ])].slice(0, 20);

  const amenityCandidates = amenityLabels.filter((label) => text.toLowerCase().includes(label.toLowerCase()));
  if (bathroom.amenity && !amenityCandidates.includes(bathroom.amenity)) {
    amenityCandidates.push(bathroom.amenity);
  }

  const rooms = roomsMatch ? Number.parseInt(roomsMatch[1], 10) : null;
  const parsedGuests = guestsMatch ? Number.parseInt(guestsMatch[1], 10) : 0;
  const floor = stackedFloor
    ? Number.parseInt(stackedFloor[1], 10)
    : simpleFloor
      ? Number.parseInt(simpleFloor[1], 10)
      : null;
  const totalFloors = stackedFloor ? Number.parseInt(stackedFloor[2], 10) : null;
  const idFromMeta = meta.externalId ? String(meta.externalId) : '';
  const idFromUrl = url.replace(/\/+$/, '').split('/').pop() || String(Date.now());

  return {
    externalId: idFromMeta || idFromUrl,
    url,
    title,
    description,
    cityName,
    streetName: address.streetName,
    building: address.building,
    latitude: null,
    longitude: null,
    priceByn,
    area: areaMatch ? Number.parseFloat(areaMatch[1].replace(',', '.')) : 0,
    rooms,
    floor,
    totalFloors: totalFloors && floor && totalFloors < floor ? floor : totalFloors,
    bathrooms: bathroom.bathrooms,
    bathroomType: bathroom.bathroomType,
    maxDailyGuests: Math.max(parsedGuests || 0, rooms || 0, 2),
    guestsParsed: parsedGuests || null,
    dailySingleBeds: rooms ? Math.max(0, rooms) : 2,
    dailyDoubleBeds: 1,
    checkInTime: firstMatch(text, /заезд[^\d]{0,20}(\d{1,2}:\d{2})/i) || '14:00',
    checkOutTime: firstMatch(text, /отъезд[^\d]{0,20}(\d{1,2}:\d{2})/i) || '12:00',
    minStayDays: 1,
    amenities: amenityCandidates,
    imageUrls: uniqueImages,
  };
}

async function fetchJsonInBrowser(page, url) {
  const result = await page.evaluate(async (target) => {
    const response = await fetch(target, { credentials: 'include' });
    const text = await response.text();
    let json = null;
    try {
      json = JSON.parse(text);
    } catch {
      json = null;
    }
    return {
      ok: response.ok,
      status: response.status,
      preview: text.slice(0, 120),
      json,
    };
  }, url);
  if (!result.ok || result.json === null) {
    throw new Error(`HTTP ${result.status} non-json (${result.preview})`);
  }
  return result.json;
}

async function fetchCatalog(page) {
  const collected = [];
  for (let catalogPage = 1; catalogPage <= 50; catalogPage += 1) {
    const url = `${API_URL}?per_page=100&page=${catalogPage}&_fields=id,link,slug,title,yoast_head_json`;
    try {
      const data = await fetchJsonInBrowser(page, url);
      if (!Array.isArray(data) || data.length === 0) {
        break;
      }
      collected.push(...data);
      console.log(`Catalog page ${catalogPage}: ${data.length} (total ${collected.length})`);
      if (data.length < 100) {
        break;
      }
    } catch (error) {
      if (catalogPage === 1) {
        throw error;
      }
      console.warn('Catalog page failed', catalogPage, error.message);
      break;
    }
    await sleep(DELAY_MS);
  }
  return collected;
}

async function fetchMediaUrls(page, postId) {
  try {
    const data = await fetchJsonInBrowser(
      page,
      `https://arendom.com/wp-json/wp/v2/media?parent=${postId}&per_page=40&_fields=source_url,mime_type`,
    );
    if (!Array.isArray(data)) {
      return [];
    }
    return data
      .filter((item) => String(item.mime_type || '').startsWith('image/'))
      .map((item) => item.source_url)
      .filter(isListingPhotoUrl);
  } catch {
    return [];
  }
}

function cityFromTerms(terms) {
  if (!Array.isArray(terms)) {
    return '';
  }
  const names = terms
    .flat(2)
    .filter((term) => term && term.taxonomy === 'mestopolozhenie' && typeof term.name === 'string')
    .map((term) => term.name)
    .filter((name) => !/област/i.test(name));
  return names.at(-1) || '';
}

async function downloadImages(page, listing, imagesDir) {
  const dir = path.join(imagesDir, listing.externalId);
  await ensureDir(dir);
  const local = [];
  for (const [index, url] of listing.imageUrls.entries()) {
    try {
      const result = await page.evaluate(async (target) => {
        const response = await fetch(target, { credentials: 'include' });
        if (!response.ok) {
          return { ok: false, status: response.status };
        }
        const contentType = response.headers.get('content-type') || '';
        const buffer = await response.arrayBuffer();
        const bytes = new Uint8Array(buffer);
        const chunk = 0x8000;
        let binary = '';
        for (let i = 0; i < bytes.length; i += chunk) {
          binary += String.fromCharCode(...bytes.subarray(i, i + chunk));
        }
        return {
          ok: true,
          contentType,
          size: bytes.length,
          b64: btoa(binary),
        };
      }, url);
      if (!result?.ok || !result.b64 || result.size < 20_000 || /html/i.test(result.contentType || '')) {
        console.warn('Skip image', url, result?.contentType || result?.status);
        continue;
      }
      const bytes = Buffer.from(result.b64, 'base64');
      const md5 = createHash('md5').update(bytes).digest('hex');
      if (skipImageMd5.has(md5)) {
        console.warn('Skip chrome image', url);
        continue;
      }
      const ext = /png/i.test(result.contentType) || url.includes('.png')
        ? 'png'
        : /webp/i.test(result.contentType) || url.includes('.webp')
          ? 'webp'
          : 'jpg';
      const fileName = `${String(index).padStart(2, '0')}.${ext}`;
      await fs.writeFile(path.join(dir, fileName), bytes);
      local.push(path.posix.join('images', listing.externalId, fileName));
    } catch (error) {
      console.warn('Image failed', url, error.message);
    }
  }
  return local;
}

async function readPageContent(page, url) {
  try {
    const result = await page.evaluate(async (target) => {
      const response = await fetch(target, { credentials: 'include' });
      const html = await response.text();
      return { ok: response.ok, html };
    }, url);
    if (result.ok && result.html && !/one moment, please/i.test(result.html) && /квартир|апартамент|этаж/i.test(result.html)) {
      const text = decode(
        result.html
          .replace(/<script[\s\S]*?<\/script>/gi, ' ')
          .replace(/<style[\s\S]*?<\/style>/gi, ' ')
          .replace(/<[^>]+>/g, ' '),
      );
      return { html: result.html, text };
    }
  } catch {
    // fall through to a real navigation
  }

  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60_000 });
  await page.waitForTimeout(600);
  const html = await page.content();
  const text = await page.locator('body').innerText();
  return { html, text };
}

async function main() {
  const limit = parseLimitFromArgv();
  await ensureDir(OUTPUT_DIR);
  const outFile = path.join(OUTPUT_DIR, 'listings.json');
  const imagesDir = path.join(OUTPUT_DIR, 'images');
  await ensureDir(imagesDir);

  const state = await loadState(outFile);
  const mapsCache = new Map();
  const seen = new Set(
    state.listings
      .filter((item) => hasEnoughPhotos(item) && hasCoordinates(item))
      .map((item) => String(item.externalId)),
  );

  const browser = await chromium.launch({
    headless: true,
    args: [
      '--disable-blink-features=AutomationControlled',
      '--window-size=1440,900',
    ],
  });
  const context = await browser.newContext({
    locale: 'ru-RU',
    viewport: { width: 1440, height: 900 },
    userAgent: CHROME_UA,
  });
  await context.addInitScript((ua) => {
    Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
    Object.defineProperty(navigator, 'languages', { get: () => ['ru-RU', 'ru', 'en-US', 'en'] });
    Object.defineProperty(navigator, 'language', { get: () => 'ru-RU' });
    Object.defineProperty(navigator, 'userAgent', { get: () => ua });
    Object.defineProperty(navigator, 'appVersion', { get: () => ua.replace(/^Mozilla\//, '') });
    Object.defineProperty(window, 'outerWidth', { get: () => window.innerWidth || 1440 });
    Object.defineProperty(window, 'outerHeight', { get: () => window.innerHeight || 900 });
  }, CHROME_UA);
  const page = await context.newPage();

  console.log('Opening', START_URL);
  await waitForSite(page);

  let catalog = [];
  try {
    catalog = await fetchCatalog(page);
  } catch (error) {
    console.warn('REST catalog failed, falling back to archive HTML:', error.message);
  }

  if (catalog.length === 0) {
    const hrefs = [];
    for (let archivePage = 1; archivePage <= 40; archivePage += 1) {
      const archiveUrl = archivePage === 1
        ? 'https://arendom.com/kvartiry/'
        : `https://arendom.com/kvartiry/page/${archivePage}/`;
      await page.goto(archiveUrl, { waitUntil: 'domcontentloaded', timeout: 60_000 });
      await page.waitForFunction(
        () => !/one moment/i.test(document.title) && !document.body?.innerText?.includes('One moment, please'),
        null,
        { timeout: 60_000 },
      );
      await page.waitForTimeout(500);
      const pageHrefs = await page.$$eval('a[href]', (anchors) => anchors.map((anchor) => anchor.href));
      const listingHrefs = [...new Set(pageHrefs.map((href) => href.split('#')[0]).filter(looksLikeListingUrl))];
      if (archivePage === 1) {
        console.log('Archive sample hrefs:', pageHrefs.slice(0, 15));
      }
      if (listingHrefs.length === 0) {
        break;
      }
      hrefs.push(...listingHrefs);
      console.log(`Archive page ${archivePage}: ${listingHrefs.length} links`);
      await sleep(DELAY_MS);
    }
    catalog = [...new Set(hrefs)].map((link) => ({
      id: link.replace(/\/+$/, '').split('/').pop(),
      link,
      title: { rendered: '' },
      yoast_head_json: {},
    }));
  }

  console.log('Candidate listings:', catalog.length);
  let processedThisRun = 0;
  let consecutiveNetworkErrors = 0;

  for (const item of catalog) {
    if (limit > 0 && processedThisRun >= limit) {
      break;
    }
    const externalId = String(item.id ?? item.slug ?? item.link?.replace(/\/+$/, '').split('/').pop() ?? '');
    const url = item.link || `https://arendom.com/kvartiry/${item.slug}/`;
    if (!externalId || seen.has(externalId)) {
      continue;
    }

    const existing = state.listings.find((row) => String(row.externalId) === externalId) ?? null;
    const reusePhotos = hasEnoughPhotos(existing);

    try {
      const imageUrls = reusePhotos || !/^\d+$/.test(externalId)
        ? []
        : await fetchMediaUrls(page, externalId);
      const { html, text } = await readPageContent(page, url);
      const listing = extractListing(url, html, text, {
        externalId,
        title: item.title?.rendered,
        description: item.yoast_head_json?.description,
        cityName: cityFromTerms(item._embedded?.['wp:term']),
        imageUrls,
      });
      if (!listing.title && listing.imageUrls.length < 3 && !reusePhotos) {
        console.log('Skip (too little data):', url);
        continue;
      }
      const coords = await resolveListingCoordinates(html, mapsCache, page);
      if (coords) {
        listing.latitude = coords.latitude;
        listing.longitude = coords.longitude;
      } else {
        console.warn('No Yandex map coordinates:', listing.externalId, url);
      }
      if (reusePhotos) {
        listing.images = existing.images;
      } else {
        listing.images = await downloadImages(page, listing, imagesDir);
        if (listing.images.length === 0 && listing.imageUrls.length > 0) {
          console.log('Refreshing challenge after empty photo set');
          await waitForSite(page);
          listing.images = await downloadImages(page, listing, imagesDir);
        }
      }
      delete listing.imageUrls;
      const existingIndex = state.listings.findIndex((row) => String(row.externalId) === listing.externalId);
      if (existingIndex >= 0) {
        state.listings[existingIndex] = listing;
      } else {
        state.listings.push(listing);
      }
      if (hasEnoughPhotos(listing) && hasCoordinates(listing)) {
        seen.add(listing.externalId);
      }
      await fs.writeFile(outFile, JSON.stringify(state, null, 2));
      processedThisRun += 1;
      consecutiveNetworkErrors = 0;
      const coordsLabel = hasCoordinates(listing)
        ? `${listing.latitude},${listing.longitude}`
        : 'no-coords';
      console.log('Saved', listing.externalId, listing.title, `${listing.images.length} photos`, coordsLabel);
      await sleep(DELAY_MS);
    } catch (error) {
      console.warn('Failed', url, error.message);
      consecutiveNetworkErrors += 1;
      if (/ERR_NAME_NOT_RESOLVED|Timeout|net::|non-json/i.test(String(error.message))) {
        const waitMs = Math.min(15_000, 2000 * consecutiveNetworkErrors);
        console.log(`Network issue, waiting ${waitMs}ms and refreshing session`);
        await sleep(waitMs);
        try {
          await waitForSite(page);
        } catch (recoverError) {
          console.warn('Session refresh failed', recoverError.message);
        }
        if (consecutiveNetworkErrors >= 8) {
          console.warn('Too many consecutive network errors, stopping this run. Re-run to resume.');
          break;
        }
      }
    }
  }

  await browser.close();
  console.log('Wrote', state.listings.length, 'listings to', outFile);
}

if (path.resolve(process.argv[1] ?? '') === fileURLToPath(import.meta.url)) {
  main().catch((error) => {
    console.error(error);
    process.exit(1);
  });
}

export {
  collectYandexMapsUrls,
  parseCoordinatesFromMapsUrl,
  resolveListingCoordinates,
  resolveYandexMapsCoordinates,
};
