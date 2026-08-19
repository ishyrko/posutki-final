import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { SiteJsonLd } from "@/components/seo/SiteJsonLd";
import { ApartmentCatalogSlugProvider } from "@/components/ApartmentCatalogSlugProvider";
import { configureApartmentCatalogSlugs } from "@/features/catalog/apartment-catalog-slug-store";
import { fetchApartmentCatalogSlugSets } from "@/lib/apartment-catalog-slugs-server";

export default async function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const slugSets = await fetchApartmentCatalogSlugSets();
    configureApartmentCatalogSlugs(slugSets);

    return (
        <ApartmentCatalogSlugProvider sets={slugSets}>
            <div className="min-h-screen">
                <SiteJsonLd />
                <Header />
                <main>
                    {children}
                </main>
                <Footer />
            </div>
        </ApartmentCatalogSlugProvider>
    );
}
