/** Baked into the client bundle at build time; matches `.next/BUILD_ID` on the server. */
export const CLIENT_BUILD_ID = process.env.NEXT_PUBLIC_BUILD_ID ?? "";
