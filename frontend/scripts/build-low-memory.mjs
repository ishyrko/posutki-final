import { spawnSync } from "node:child_process";
import { rmSync } from "node:fs";
import path from "node:path";

/**
 * CloudLinux ulimit -v: heap + SWC + webpack peak должны влезать в ~2–3 GB VM.
 * При ENOMEM уменьшайте CPANEL_BUILD_HEAP_MB (не ниже 1024).
 */
const heapMb = Number.parseInt(process.env.CPANEL_BUILD_HEAP_MB ?? "1536", 10);
const heapClamped = Math.min(Math.max(Number.isFinite(heapMb) ? heapMb : 1536, 1024), 2048);

/** Defaults for cPanel / CloudLinux (virtual memory limit + low RSS). */
const DEFAULT_NODE_OPTIONS = [
  `--max-old-space-size=${heapClamped}`,
  "--disable-wasm-trap-handler",
];

function mergeNodeOptions(existing) {
  const parts = (existing ?? "")
    .split(/\s+/)
    .map((s) => s.trim())
    .filter(Boolean);
  for (const flag of DEFAULT_NODE_OPTIONS) {
    const key = flag.split("=")[0];
    if (!parts.some((p) => p === flag || p.startsWith(`${key}=`))) {
      parts.push(flag);
    }
  }
  return parts.join(" ");
}

process.env.NEXT_TELEMETRY_DISABLED = "1";
process.env.NEXT_LOW_MEMORY_BUILD = "1";
if (process.env.CPANEL_BUILD_NO_WEBPACK_CACHE !== "0") {
  process.env.CPANEL_BUILD_NO_WEBPACK_CACHE = "1";
}
process.env.NODE_OPTIONS = mergeNodeOptions(process.env.NODE_OPTIONS);
console.log(`[build:low-memory] NODE_OPTIONS=${process.env.NODE_OPTIONS}`);
console.log(
  `[build:low-memory] heap=${heapClamped}MB clean=${process.env.CPANEL_BUILD_CLEAN !== "0"} noWebpackCache=${process.env.CPANEL_BUILD_NO_WEBPACK_CACHE === "1"}`,
);

if (process.env.CPANEL_BUILD_CLEAN !== "0") {
  for (const rel of [".next/cache", "node_modules/.cache"]) {
    try {
      rmSync(path.join(process.cwd(), rel), { recursive: true, force: true });
      console.log(`[build:low-memory] cleared ${rel}`);
    } catch {
      // ignore
    }
  }
}

const result = spawnSync("npx", ["next", "build", "--webpack"], {
  stdio: "inherit",
  env: process.env,
  shell: false,
});

if ((result.status ?? 1) !== 0) {
  console.error(
    "[build:low-memory] Сборка не удалась. При «memory allocation» на CloudLinux попробуйте env:\n" +
      "  CPANEL_BUILD_HEAP_MB=1280  (или 1024)\n" +
      "  CPANEL_BUILD_CLEAN=1\n" +
      "  CPANEL_BUILD_NO_WEBPACK_CACHE=1\n" +
      "Либо соберите локально: make frontend-build-cpanel-prod и залейте frontend/.next",
  );
}

process.exit(result.status ?? 1);
