import type { StaticPage } from "@/features/staticPages/types";
import { fetchPublicApi } from "@/lib/server-api";

export async function fetchStaticPage(slug: string): Promise<StaticPage | null> {
  try {
    return await fetchPublicApi<StaticPage>(
      `/static-pages/${encodeURIComponent(slug)}`,
      {
        cache: "force-cache",
        next: {
          tags: ["static-pages", `static-page-${slug}`],
        },
      },
    );
  } catch {
    return null;
  }
}
