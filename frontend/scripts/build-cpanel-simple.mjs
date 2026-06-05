import { spawnSync } from "node:child_process";

/**
 * Минимальная сборка для cPanel: только WASM-fix и опциональный heap.
 * Без NEXT_LOW_MEMORY_BUILD (experimental cpus/webpack tweaks) — ближе к «вчерашнему» npm run build.
 */
const heapMb = process.env.CPANEL_BUILD_HEAP_MB?.trim();
const nodeOpts = ["--disable-wasm-trap-handler"];
if (heapMb) {
  nodeOpts.unshift(`--max-old-space-size=${heapMb}`);
}

process.env.NEXT_TELEMETRY_DISABLED = "1";
process.env.NODE_OPTIONS = [process.env.NODE_OPTIONS, ...nodeOpts]
  .filter(Boolean)
  .join(" ")
  .trim();
console.log(`[build:cpanel-simple] NODE_OPTIONS=${process.env.NODE_OPTIONS}`);

const result = spawnSync("npx", ["next", "build", "--webpack"], {
  stdio: "inherit",
  env: process.env,
  shell: false,
});

process.exit(result.status ?? 1);
