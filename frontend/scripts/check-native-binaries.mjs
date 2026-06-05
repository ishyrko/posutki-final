import { createRequire } from "node:module";
import { arch, platform } from "node:os";

const require = createRequire(import.meta.url);

/** @type {{ name: string; packages: string[] }[]} */
const groups = [];

if (platform() === "linux" && arch() === "x64") {
  groups.push(
    {
      name: "Next.js SWC",
      packages: ["@next/swc-linux-x64-gnu", "@next/swc-linux-x64-musl"],
    },
    {
      name: "Tailwind Oxide",
      packages: ["@tailwindcss/oxide-linux-x64-gnu", "@tailwindcss/oxide-linux-x64-musl"],
    },
    {
      name: "Lightning CSS",
      packages: ["lightningcss-linux-x64-gnu", "lightningcss-linux-x64-musl"],
    },
  );
} else if (platform() === "linux" && arch() === "arm64") {
  groups.push(
    {
      name: "Next.js SWC",
      packages: ["@next/swc-linux-arm64-gnu", "@next/swc-linux-arm64-musl"],
    },
    {
      name: "Tailwind Oxide",
      packages: ["@tailwindcss/oxide-linux-arm64-gnu", "@tailwindcss/oxide-linux-arm64-musl"],
    },
    {
      name: "Lightning CSS",
      packages: ["lightningcss-linux-arm64-gnu", "lightningcss-linux-arm64-musl"],
    },
  );
} else if (platform() === "darwin" && arch() === "arm64") {
  groups.push(
    { name: "Next.js SWC", packages: ["@next/swc-darwin-arm64"] },
    { name: "Tailwind Oxide", packages: ["@tailwindcss/oxide-darwin-arm64"] },
    { name: "Lightning CSS", packages: ["lightningcss-darwin-arm64"] },
  );
} else if (platform() === "darwin" && arch() === "x64") {
  groups.push(
    { name: "Next.js SWC", packages: ["@next/swc-darwin-x64"] },
    { name: "Tailwind Oxide", packages: ["@tailwindcss/oxide-darwin-x64"] },
    { name: "Lightning CSS", packages: ["lightningcss-darwin-x64"] },
  );
} else if (platform() === "win32" && arch() === "x64") {
  groups.push(
    { name: "Next.js SWC", packages: ["@next/swc-win32-x64-msvc"] },
    { name: "Tailwind Oxide", packages: ["@tailwindcss/oxide-win32-x64-msvc"] },
    { name: "Lightning CSS", packages: ["lightningcss-win32-x64-msvc"] },
  );
}

if (groups.length === 0) {
  console.warn(
    `[check-native] Unsupported platform ${platform()}/${arch()}; skipping native binary check.`,
  );
  process.exit(0);
}

/** @type {string[]} */
const missing = [];

for (const group of groups) {
  let found = false;
  for (const pkg of group.packages) {
    try {
      require.resolve(pkg);
      console.log(`[check-native] ${group.name}: ${pkg}`);
      found = true;
      break;
    } catch {
      // try next candidate (gnu / musl)
    }
  }
  if (!found) {
    missing.push(group.name);
  }
}

let wasmFallback = false;
try {
  require.resolve("@tailwindcss/oxide-wasm32-wasi");
  wasmFallback = true;
} catch {
  // no wasm package — good
}

if (missing.length === 0) {
  if (wasmFallback && platform() === "linux") {
    console.warn(
      "[check-native] @tailwindcss/oxide-wasm32-wasi is installed — build may use WASM if native load fails.",
    );
  }
  process.exit(0);
}

console.error(
  [
    `[check-native] Missing native binaries: ${missing.join(", ")}`,
    "npm will fall back to WebAssembly → WebAssembly.instantiate(): Out of memory on shared hosting.",
    "On the server run: npm run install:cpanel",
    "Do not use --omit=optional. Do not copy node_modules from macOS/Windows.",
    "If natives are OK but build still WASM-OOM: add NODE_OPTIONS='--disable-wasm-trap-handler' (CloudLinux virtual memory limit).",
    "Last resort: make frontend-build-cpanel-prod on Mac and upload .next.",
  ].join("\n"),
);
process.exit(1);
