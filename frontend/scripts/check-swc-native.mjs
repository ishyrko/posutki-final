import { spawnSync } from "node:child_process";
import { fileURLToPath } from "node:url";
import path from "node:path";

const dir = path.dirname(fileURLToPath(import.meta.url));
const target = path.join(dir, "check-native-binaries.mjs");

const result = spawnSync(process.execPath, [target], { stdio: "inherit" });
process.exit(result.status ?? 1);
