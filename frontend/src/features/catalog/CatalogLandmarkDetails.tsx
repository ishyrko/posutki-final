import { Lightbulb, MapPin, Train } from "lucide-react";
import { formatLandmarkDistance } from "@/features/landmarks/distance";
import type { Landmark } from "@/features/landmarks/types";

type CatalogLandmarkDetailsProps = {
  landmark: Landmark;
  descriptionHtml?: string | null;
};

/** SSR block below landmark catalog: description, facts, guest tips and info sidebar. */
export default function CatalogLandmarkDetails({
  landmark,
  descriptionHtml,
}: CatalogLandmarkDetailsProps) {
  const facts = landmark.facts ?? [];
  const guestTips = landmark.guestTips ?? [];
  const hasSidebar = Boolean(landmark.address || landmark.nearestMetro);
  const hasMainContent = Boolean(descriptionHtml) || facts.length > 0 || guestTips.length > 0;

  if (!hasSidebar && !hasMainContent) {
    return null;
  }

  return (
    <section className="mt-16 border-t border-border pt-10">
      <h2 className="font-display text-2xl font-bold text-foreground mb-6">
        О достопримечательности: {landmark.name}
      </h2>

      <div className="grid lg:grid-cols-3 gap-10">
        {hasMainContent ? (
          <div className="lg:col-span-2 space-y-4">
            {descriptionHtml ? (
              <div
                className="prose prose-sm max-w-none prose-neutral prose-headings:font-semibold prose-p:text-muted-foreground prose-li:text-muted-foreground dark:prose-invert [&_a]:text-primary [&_a]:underline [&_img]:my-6 [&_img]:block [&_img]:h-auto [&_img]:w-full [&_img]:rounded-lg"
                dangerouslySetInnerHTML={{ __html: descriptionHtml }}
              />
            ) : null}

            {facts.length > 0 ? (
              <div className="grid sm:grid-cols-2 gap-4 pt-4">
                {facts.map((fact) => (
                  <div key={`${fact.label}-${fact.value}`} className="rounded-xl border border-border bg-card p-4">
                    <p className="text-xs uppercase tracking-wide text-muted-foreground mb-1">
                      {fact.label}
                    </p>
                    <p className="font-semibold text-foreground">{fact.value}</p>
                  </div>
                ))}
              </div>
            ) : null}

            {guestTips.length > 0 ? (
              <div className="rounded-2xl border border-border bg-accent/30 p-5 mt-4">
                <h3 className="font-display font-semibold text-foreground mb-3 flex items-center gap-2">
                  <Lightbulb className="h-4 w-4 text-primary" />
                  Советы гостям
                </h3>
                <ul className="space-y-2">
                  {guestTips.map((tip) => (
                    <li key={tip} className="text-sm text-muted-foreground flex gap-2">
                      <span className="text-primary">•</span>
                      <span>{tip}</span>
                    </li>
                  ))}
                </ul>
              </div>
            ) : null}
          </div>
        ) : null}

        {hasSidebar ? (
          <aside className={hasMainContent ? "" : "lg:col-span-3"}>
            <div className="rounded-2xl border border-border bg-card p-5">
              <h3 className="font-display font-semibold text-foreground mb-4">Информация</h3>
              <ul className="space-y-4 text-sm">
                {landmark.address ? (
                  <li className="flex gap-3">
                    <MapPin className="h-4 w-4 text-primary shrink-0 mt-0.5" />
                    <span className="text-muted-foreground">{landmark.address}</span>
                  </li>
                ) : null}
                {landmark.nearestMetro ? (
                  <li className="flex gap-3">
                    <Train className="h-4 w-4 text-primary shrink-0 mt-0.5" />
                    <span className="text-muted-foreground">
                      Метро «{landmark.nearestMetro.name}»
                      {landmark.nearestMetro.distanceKm > 0 ? (
                        <span className="text-muted-foreground/80">
                          {" "}
                          — {formatLandmarkDistance(landmark.nearestMetro.distanceKm)}
                        </span>
                      ) : null}
                    </span>
                  </li>
                ) : null}
              </ul>
            </div>
          </aside>
        ) : null}
      </div>
    </section>
  );
}
