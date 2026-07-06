import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // The SPA calls Symfony directly via NEXT_PUBLIC_SYMFONY_URL.
  // No proxy rewrites needed.
  allowedDevOrigins: ["nextjs.playground.dev"],
};

export default nextConfig;
