type ApiResponse<T> = {
  data: T;
};

import { cookies } from "next/headers";
import { AUTH_TOKEN_KEY } from "@/lib/auth-constants";

type FetchApiOptions = {
  cache?: RequestCache;
  next?: {
    revalidate?: number;
    tags?: string[];
  };
};

function resolveApiBaseUrl(): string {
  const internalBase = process.env.BACKEND_INTERNAL_URL;
  if (internalBase) {
    return `${internalBase.replace(/\/+$/, "")}/api`;
  }

  const publicBase = process.env.NEXT_PUBLIC_API_URL;
  if (publicBase) {
    return publicBase.replace(/\/+$/, "");
  }

  throw new Error("API base URL is not configured");
}

async function fetchFromApi<T>(
  path: string,
  options: FetchApiOptions,
  headers: Record<string, string> | undefined,
): Promise<T> {
  const baseUrl = resolveApiBaseUrl();

  const response = await fetch(`${baseUrl}${path}`, {
    cache: options.cache ?? "no-store",
    next: options.next,
    headers,
  });

  if (!response.ok) {
    throw new Error(`Failed to fetch ${path}: ${response.status}`);
  }

  const payload = (await response.json()) as ApiResponse<T>;
  return payload.data;
}

/** Public GET requests: no auth headers (avoids JWT side effects on anonymous pages). */
export async function fetchPublicApi<T>(
  path: string,
  options: FetchApiOptions = {},
): Promise<T> {
  return fetchFromApi<T>(path, options, undefined);
}

/**
 * Same as fetchPublicApi but returns null on HTTP 404 (does not throw).
 * Other error statuses still throw — avoids caching a fake 404 when the API returns 5xx.
 */
export async function fetchPublicApiNullable<T>(
  path: string,
  options: FetchApiOptions = {},
): Promise<T | null> {
  const baseUrl = resolveApiBaseUrl();

  const response = await fetch(`${baseUrl}${path}`, {
    cache: options.cache ?? "no-store",
    next: options.next,
  });

  if (response.status === 404) {
    return null;
  }

  if (!response.ok) {
    throw new Error(`Failed to fetch ${path}: ${response.status}`);
  }

  const payload = (await response.json()) as ApiResponse<T>;
  return payload.data;
}

export async function fetchApi<T>(
  path: string,
  options: FetchApiOptions = {},
): Promise<T> {
  const headers: Record<string, string> = {};
  const cookieStore = await cookies();
  const rawToken = cookieStore.get(AUTH_TOKEN_KEY)?.value;
  if (rawToken) {
    try {
      headers.Authorization = `Bearer ${decodeURIComponent(rawToken)}`;
    } catch {
      headers.Authorization = `Bearer ${rawToken}`;
    }
  }

  return fetchFromApi<T>(
    path,
    options,
    Object.keys(headers).length > 0 ? headers : undefined,
  );
}
