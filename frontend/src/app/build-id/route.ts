import { readBuildId } from "@/lib/build-id";

export const dynamic = "force-dynamic";

export function GET() {
  return new Response(readBuildId(), {
    headers: {
      "Content-Type": "text/plain; charset=utf-8",
      "Cache-Control": "no-store, no-cache, must-revalidate",
    },
  });
}
