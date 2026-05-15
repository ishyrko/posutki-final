import { fetchPublicApi } from "@/lib/server-api";
import type { Article } from "@/features/articles/types";

const HOME_ARTICLES_LIMIT = 3;

export async function fetchRecentArticlesForHome(): Promise<Article[]> {
  try {
    return await fetchPublicApi<Article[]>(
      `/articles?status=published&limit=${HOME_ARTICLES_LIMIT}`,
      {
        next: { revalidate: 120, tags: ["articles"] },
      },
    );
  } catch {
    return [];
  }
}
