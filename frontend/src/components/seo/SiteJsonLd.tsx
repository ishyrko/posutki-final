import { JsonLdScript } from "@/lib/json-ld/json-ld-script";
import { buildSiteJsonLd } from "@/lib/json-ld/site";

export function SiteJsonLd() {
  const graph = buildSiteJsonLd();

  return (
    <JsonLdScript
      data={{
        "@context": "https://schema.org",
        "@graph": graph,
      }}
    />
  );
}
