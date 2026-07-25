import { spawnSync } from "node:child_process";
import { rmSync } from "node:fs";
import path from "node:path";

/**
 * CloudLinux ulimit -v (часто 4 GB): heap V8 + SWC + webpack должны влезать в VM.
 * «JavaScript heap out of memory» → увеличьте CPANEL_BUILD_HEAP_MB (до 2816).
 * «memory allocation» / native OOM → уменьшите heap (от 1536).
 */
const heapMb = Number.parseInt(process.env.CPANEL_BUILD_HEAP_MB ?? "2048", 10);
const heapClamped = Math.min(Math.max(Number.isFinite(heapMb) ? heapMb : 2048, 1024), 2816);

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
process.env.UV_THREADPOOL_SIZE = process.env.UV_THREADPOOL_SIZE ?? "1";
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
    "[build:low-memory] Сборка не удалась. Подстройте heap под текст ошибки:\n" +
      "  «JavaScript heap out of memory» → CPANEL_BUILD_HEAP_MB=2560 (или 2816)\n" +
      "  «memory allocation» / native OOM → CPANEL_BUILD_HEAP_MB=1792 (или 1536)\n" +
      "  CPANEL_BUILD_CLEAN=1\n" +
      "Либо: make frontend-build-cpanel-prod и залейте frontend/.next",
  );
}

process.exit(result.status ?? 1);
