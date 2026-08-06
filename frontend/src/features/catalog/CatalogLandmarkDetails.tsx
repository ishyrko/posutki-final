import Link from "next/link";
import { Lightbulb, MapPin, Train } from "lucide-react";
import { formatLandmarkDistance } from "@/features/landmarks/distance";
import { resolveLandmarkImageUrl } from "@/features/landmarks/image";
import type { Landmark, LandmarkListItem } from "@/features/landmarks/types";
import { buildLandmarkCatalogUrl, resolveCatalogCityNominative } from "@/features/catalog/slugs";

type CatalogLandmarkDetailsProps = {
  landmark: Landmark;
  citySlug: string;
  relatedLandmarks?: LandmarkListItem[];
  descriptionHtml?: string | null;
};

/** Landmark catalog footer: description, facts, related landmarks (rendered from CatalogPage with server-prepared data). */
export default function CatalogLandmarkDetails({
  landmark,
  citySlug,
  relatedLandmarks = [],
  descriptionHtml,
}: CatalogLandmarkDetailsProps) {
  const facts = landmark.facts ?? [];
  const guestTips = landmark.guestTips ?? [];
  const hasSidebar = Boolean(landmark.address || landmark.nearestMetro);
  const hasMainContent = Boolean(descriptionHtml) || facts.length > 0 || guestTips.length > 0;
  const hasRelatedLandmarks = relatedLandmarks.length > 0;
  const cityLabel = resolveCatalogCityNominative(citySlug);

  if (!hasSidebar && !hasMainContent && !hasRelatedLandmarks) {
    return null;
  }

  return (
    <section className="mt-16 border-t border-border pt-10">
      {hasSidebar || hasMainContent ? (
        <>
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
        </>
      ) : null}

      {hasRelatedLandmarks ? (
        <div className={hasSidebar || hasMainContent ? "mt-12" : ""}>
          <h3 className="font-display text-xl font-bold text-foreground mb-6">
            Квартиры возле других достопримечательностей
          </h3>
          <div className="grid sm:grid-cols-3 gap-6">
            {relatedLandmarks.map((related) => {
              const imageUrl = resolveLandmarkImageUrl(related.imageUrl);
              const href = buildLandmarkCatalogUrl(citySlug, related.slug);

              return (
                <Link
                  key={related.id}
                  href={href}
                  className="group rounded-2xl border border-border bg-card overflow-hidden hover:shadow-card-hover transition-shadow"
                >
                  <div className="aspect-[16/10] overflow-hidden bg-muted">
                    {imageUrl ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img
                        src={imageUrl}
                        alt={related.name}
                        width={1600}
                        height={900}
                        loading="lazy"
                        className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                      />
                    ) : null}
                  </div>
                  <div className="p-4">
                    <h4 className="font-semibold text-foreground transition-colors group-hover:text-primary">
                      Квартиры возле «{related.nameGenitive ?? related.name}»
                    </h4>
                    <p className="mt-1 text-xs text-muted-foreground">{cityLabel}</p>
                  </div>
                </Link>
              );
            })}
          </div>
        </div>
      ) : null}
    </section>
  );
}
