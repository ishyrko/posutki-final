import type { NextConfig } from "next";
import path from "path";

const nextConfig: NextConfig = {
  trailingSlash: true,
  /** CJS sanitizer: load from node_modules at runtime (avoids bundler edge cases). */
  serverExternalPackages: ["sanitize-html"],
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
  },
};

export default nextConfig;
