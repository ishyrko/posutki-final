import { readFile } from "node:fs/promises";
import path from "node:path";

export async function GET() {
  const candidates = [
    path.join(process.cwd(), "public", "brand", "logo.png"),
    path.join(process.cwd(), "public", "brand", "logo-transparent.png"),
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

