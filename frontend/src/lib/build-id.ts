import { readFileSync } from "fs";
import path from "path";

/** Next.js BUILD_ID from `.next/BUILD_ID` (unique per production build). */
export function readBuildId(): string {
  if (process.env.NODE_ENV === "development") {
    return "dev";
  }

  try {
    return readFileSync(path.join(process.cwd(), ".next/BUILD_ID"), "utf8").trim();
  } catch {
    return "unknown";
  }
}
