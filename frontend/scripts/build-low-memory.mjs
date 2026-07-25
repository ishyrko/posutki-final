import { spawnSync } from "node:child_process";
import { rmSync } from "node:fs";
import path from "node:path";

/**
 * CloudLinux ulimit -v (часто 4 GB): heap V8 + mmap нативных .node (SWC, Oxide) в одном адресном пространстве.
 * 2560+ → SWC «Cannot allocate memory» (mmap). 1536 → webpack «heap out of memory».
 * Для 4 GB LVE: 1920–2048. «heap out of memory» → +128; native/SWC mmap → −128.
 */
const heapMb = Number.parseInt(process.env.CPANEL_BUILD_HEAP_MB ?? "2048", 10);
const heapClamped = Math.min(Math.max(Number.isFinite(heapMb) ? heapMb : 2048, 1024), 2400);

if (Number.isFinite(heapMb) && heapMb > 2400) {
  console.warn(
    `[build:low-memory] CPANEL_BUILD_HEAP_MB=${heapMb} > 2400 — clamped to ${heapClamped}. ` +
      "На 4 GB LVE большой heap блокирует загрузку SWC (mmap).",
  );
}

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
    "[build:low-memory] Сборка не удалась. На 4 GB LVE рабочий диапазон heap: 1920–2048.\n" +
      "  SWC «Cannot allocate memory» / failed to map segment → heap слишком большой (не 2560+), попробуйте 2048 или 1920\n" +
      "  «JavaScript heap out of memory» → 2048 или 2176 (не выше 2400)\n" +
      "  CPANEL_BUILD_CLEAN=1\n" +
      "Надёжно: make frontend-build-cpanel-prod локально → залить frontend/.next",
  );
}

process.exit(result.status ?? 1);
