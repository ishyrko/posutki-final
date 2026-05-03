import type { NextConfig } from "next";
import path from "path";

const radixReactIdShim = path.resolve(__dirname, "src/lib/shims/radix-react-id.ts");

const nextConfig: NextConfig = {
  trailingSlash: true,
  /** CJS sanitizer: load from node_modules at runtime (avoids bundler edge cases). */
  serverExternalPackages: ["sanitize-html"],
  webpack: (config) => {
    const alias = config.resolve?.alias;
    const existing =
      alias && typeof alias === "object" && !Array.isArray(alias)
        ? { ...(alias as Record<string, string | string[] | false>) }
        : {};
    config.resolve = config.resolve ?? {};
    config.resolve.alias = {
      ...existing,
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
    const backendInternal = process.env.BACKEND_INTERNAL_URL || 'http://localhost';
    return [
      {
        source: '/uploads/:path*',
        destination: `${backendInternal}/uploads/:path*`,
      },
    ];
  },
  turbopack: {
    root: path.resolve(__dirname),
    /** Relative to `root` — absolute paths break Turbopack resolution (see next build). */
    resolveAlias: {
      "@radix-ui/react-id": "./src/lib/shims/radix-react-id.ts",
    },
  },
};

export default nextConfig;
