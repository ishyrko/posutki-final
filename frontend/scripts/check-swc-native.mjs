import { createRequire } from "node:module";
import { arch, platform } from "node:os";

const require = createRequire(import.meta.url);

/** @type {string[]} */
const candidates = [];

if (platform() === "linux" && arch() === "x64") {
  candidates.push("@next/swc-linux-x64-gnu", "@next/swc-linux-x64-musl");
} else if (platform() === "linux" && arch() === "arm64") {
  candidates.push("@next/swc-linux-arm64-gnu", "@next/swc-linux-arm64-musl");
} else if (platform() === "darwin" && arch() === "arm64") {
  candidates.push("@next/swc-darwin-arm64");
} else if (platform() === "darwin" && arch() === "x64") {
  candidates.push("@next/swc-darwin-x64");
} else if (platform() === "win32" && arch() === "x64") {
  candidates.push("@next/swc-win32-x64-msvc");
} else if (platform() === "win32" && arch() === "arm64") {
  candidates.push("@next/swc-win32-arm64-msvc");
}

for (const pkg of candidates) {
  try {
    require.resolve(pkg);
    console.log(`[check-swc] Native compiler found: ${pkg}`);
    process.exit(0);
  } catch {
    // try next candidate
  }
}

console.error(
  [
    "[check-swc] Native @next/swc binary not found for this platform.",
    "Without it Next.js uses WASM and often hits OOM on shared hosting (4 GB limits).",
    "Fix: run `npm install` on the server without `--omit=optional`,",
    "or build in CI/Docker and deploy the `.next` folder.",
  ].join("\n"),
);
process.exit(1);
