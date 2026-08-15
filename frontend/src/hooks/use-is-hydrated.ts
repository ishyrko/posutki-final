import * as React from "react";

/** True only after the client has hydrated (false on server and on the first client pass). */
export function useIsHydrated(): boolean {
  return React.useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );
}
