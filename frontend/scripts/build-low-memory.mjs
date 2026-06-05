import { spawnSync } from "node:child_process";
import { rmSync } from "node:fs";
import path from "node:path";

const heapMb = Number.parseInt(process.env.CPANEL_BUILD_HEAP_MB ?? "1536", 10);

/** Defaults for cPanel / CloudLinux (virtual memory limit + low RSS). */
const DEFAULT_NODE_OPTIONS = [
  `--max-old-space-size=${Number.isFinite(heapMb) ? heapMb : 1536}`,
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
process.env.NODE_OPTIONS = mergeNodeOptions(process.env.NODE_OPTIONS);
console.log(`[build:low-memory] NODE_OPTIONS=${process.env.NODE_OPTIONS}`);

for (const rel of [".next/cache", "node_modules/.cache"]) {
  try {
    rmSync(path.join(process.cwd(), rel), { recursive: true, force: true });
    console.log(`[build:low-memory] cleared ${rel}`);
  } catch {
    // ignore
  }
}

const result = spawnSync("npx", ["next", "build", "--webpack"], {
  stdio: "inherit",
  env: process.env,
  shell: false,
});

process.exit(result.status ?? 1);
