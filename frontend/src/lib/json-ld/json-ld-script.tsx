type JsonLdGraph = {
  "@context": "https://schema.org";
  "@graph": Record<string, unknown>[];
};

type JsonLdPayload = Record<string, unknown> | JsonLdGraph;

export function JsonLdScript({ data }: { data: JsonLdPayload }) {
  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: JSON.stringify(data) }}
    />
  );
}
