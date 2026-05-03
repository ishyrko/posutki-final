import * as React from "react";

/**
 * Radix's @radix-ui/react-id combines React.useId with useLayoutEffect fallbacks for
 * pre-React-18. That pattern can produce different aria-controls / id values between
 * SSR and the first client render on React 19 + Next.js. We only target React 18+ here
 * and delegate straight to useId so server and client stay aligned.
 */
export function useId(deterministicId?: string): string {
  const generated = React.useId();
  return deterministicId || (generated ? `radix-${generated}` : "");
}
