"use client";

import { useEffect, useRef } from "react";
import { toast } from "sonner";
import { CLIENT_BUILD_ID } from "@/lib/client-build-id";
import { hardReloadAfterDeploy } from "@/lib/hard-reload";

const BUILD_ID_ENDPOINT = "/build-id/";
const POLL_INTERVAL_MS = 30 * 60 * 1000;

/**
 * After a deploy, long-lived tabs keep old JS in memory. On return to the tab,
 * compare server BUILD_ID with the id baked into this bundle and offer reload.
 */
export default function DeployVersionGuard() {
  const notifiedRef = useRef(false);

  useEffect(() => {
    if (!CLIENT_BUILD_ID || CLIENT_BUILD_ID === "dev" || CLIENT_BUILD_ID === "unknown") {
      return;
    }

    let cancelled = false;

    const checkForNewBuild = async () => {
      if (notifiedRef.current || cancelled) {
        return;
      }

      try {
        const response = await fetch(BUILD_ID_ENDPOINT, {
          cache: "no-store",
          headers: { Accept: "text/plain" },
        });
        if (!response.ok || cancelled) {
          return;
        }

        const latest = (await response.text()).trim();
        if (!latest || latest === CLIENT_BUILD_ID) {
          return;
        }

        notifiedRef.current = true;
        toast.info("Доступна новая версия сайта", {
          duration: Infinity,
          action: {
            label: "Обновить",
            onClick: () => void hardReloadAfterDeploy(latest),
          },
        });
      } catch {
        /* offline or transient — skip */
      }
    };

    const onVisibilityChange = () => {
      if (document.visibilityState === "visible") {
        void checkForNewBuild();
      }
    };

    document.addEventListener("visibilitychange", onVisibilityChange);
    const intervalId = window.setInterval(checkForNewBuild, POLL_INTERVAL_MS);

    if (document.visibilityState === "visible") {
      void checkForNewBuild();
    }

    return () => {
      cancelled = true;
      document.removeEventListener("visibilitychange", onVisibilityChange);
      window.clearInterval(intervalId);
    };
  }, []);

  return null;
}
