import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { SiteJsonLd } from "@/components/seo/SiteJsonLd";
import { ApartmentCatalogSlugGate } from "@/components/ApartmentCatalogSlugGate";

export default async function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <ApartmentCatalogSlugGate>
            <div className="min-h-screen">
                <SiteJsonLd />
                <Header />
                <main>
                    {children}
                </main>
                <Footer />
            </div>
        </ApartmentCatalogSlugGate>
    );
}
