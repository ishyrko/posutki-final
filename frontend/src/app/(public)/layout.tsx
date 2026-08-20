import Header from "@/components/Header";
import Footer from "@/components/Footer";
import { SiteJsonLd } from "@/components/seo/SiteJsonLd";

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="min-h-screen">
            <SiteJsonLd />
            <Header />
            <main>
                {children}
            </main>
            <Footer />
        </div>
    );
}
