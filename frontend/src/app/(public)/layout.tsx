import Header from "@/components/Header";
import Footer from "@/components/Footer";

export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="min-h-screen">
            <Header />
            <main>
                {children}
            </main>
            <Footer />
        </div>
    );
}
