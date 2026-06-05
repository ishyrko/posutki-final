import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const radixReactIdShim = path.resolve(__dirname, "src/lib/shims/radix-react-id.ts");
const lowMemoryBuild = process.env.NEXT_LOW_MEMORY_BUILD === "1";

/** @type {import('next').NextConfig} */
const nextConfig = {
  trailingSlash: true,
  /** Shared hosting (cPanel): single worker, no webpack worker, lower webpack peak RSS. */
  ...(lowMemoryBuild
    ? {
        eslint: {
          ignoreDuringBuilds: true,
        },
        experimental: {
          webpackMemoryOptimizations: true,
          webpackBuildWorker: false,
          workerThreads: false,
          memoryBasedWorkersCount: false,
          cpus: 1,
        },
      }
    : {}),
  /** CJS sanitizer: load from node_modules at runtime (avoids bundler edge cases). */
  serverExternalPackages: ["sanitize-html"],
  webpack: (config) => {
    if (lowMemoryBuild) {
      config.parallelism = 1;
      config.cache = false;
    }

    const alias = config.resolve?.alias;
    const existing =
      alias && typeof alias === "object" && !Array.isArray(alias)
        ? { ...alias }
        : {};
    config.resolve = config.resolve ?? {};
    config.resolve.alias = {
      ...existing,
      "@": path.resolve(__dirname, "src"),
      "@radix-ui/react-id": radixReactIdShim,
    };
    return config;
  },
  typescript: {
    ignoreBuildErrors: true,
  },
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "images.unsplash.com",
      },
    ],
  },
  async redirects() {
    return [
      {
        source: "/privacy-policy",
        destination: "/politika-konfidentsialnosti/",
        permanent: true,
      },
      {
        source: "/privacy-policy/",
        destination: "/politika-konfidentsialnosti/",
        permanent: true,
      },
      {
        source: "/terms-of-service",
        destination: "/usloviya-ispolzovaniya/",
        permanent: true,
      },
      {
        source: "/terms-of-service/",
        destination: "/usloviya-ispolzovaniya/",
        permanent: true,
      },
      {
        source: "/about",
        destination: "/o-nas/",
        permanent: true,
      },
      {
        source: "/about/",
        destination: "/o-nas/",
        permanent: true,
      },
    ];
  },
  async rewrites() {
    const backendInternal = process.env.BACKEND_INTERNAL_URL || "http://localhost";
    return [
      {
        source: "/uploads/:path*",
        destination: `${backendInternal}/uploads/:path*`,
      },
    ];
  },
  turbopack: {
    root: path.resolve(__dirname),
    /** Relative to `root` — absolute paths break Turbopack resolution (see next build). */
    resolveAlias: {
      "@": "./src",
      "@radix-ui/react-id": "./src/lib/shims/radix-react-id.ts",
    },
  },
};

export default nextConfig;
