import { readFile } from "node:fs/promises";
import path from "node:path";

export async function GET() {
  const candidates = [
    // Preferred: tracked app asset location (if you later move it here)
    path.join(process.cwd(), "public", "brand", "logo-transparent.png"),
    // Fallback: existing tracked logo in repo (works in Docker too)
    path.join(process.cwd(), "public", "rnb-logo-transparent.png"),
    // Fallback: local design prototype asset (typically gitignored)
    path.join(process.cwd(), "..", "_design", "posutki-logo-transparent.png"),
  ];

  for (const filePath of candidates) {
    try {
      const bytes = await readFile(filePath);
      return new Response(bytes, {
        headers: {
          "content-type": "image/png",
          "cache-control": "public, max-age=3600, s-maxage=86400",
        },
      });
    } catch {
      // try next candidate
    }
  }

  return new Response("Logo not found", { status: 404 });
}

