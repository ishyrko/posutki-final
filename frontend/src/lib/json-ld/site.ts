import { COMPANY } from "@/lib/company";
import { getSiteOrigin } from "@/lib/site-url";

export function buildSiteJsonLd(): Record<string, unknown>[] {
  const origin = getSiteOrigin();

  const organization: Record<string, unknown> = {
    "@context": "https://schema.org",
    "@type": "Organization",
    "@id": `${origin}/#organization`,
    name: "Posutki.by",
    alternateName: "Посутки.by",
    url: `${origin}/`,
    logo: `${origin}/brand/logo.png`,
    email: COMPANY.email,
    telephone: COMPANY.phone,
    address: {
      "@type": "PostalAddress",
      streetAddress: COMPANY.address,
      addressLocality: "Минск",
      addressCountry: "BY",
    },
    contactPoint: {
      "@type": "ContactPoint",
      telephone: COMPANY.phone,
      email: COMPANY.email,
      contactType: "customer support",
      availableLanguage: ["Russian"],
      areaServed: "BY",
    },
  };

  const website: Record<string, unknown> = {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "@id": `${origin}/#website`,
    name: "Posutki.by",
    alternateName: "Посутки.by",
    url: `${origin}/`,
    publisher: { "@id": `${origin}/#organization` },
    inLanguage: "ru-BY",
    potentialAction: {
      "@type": "SearchAction",
      target: {
        "@type": "EntryPoint",
        urlTemplate: `${origin}/kvartiry/?guests={search_term_string}`,
      },
      "query-input": "required name=search_term_string",
    },
  };

  return [organization, website];
}
