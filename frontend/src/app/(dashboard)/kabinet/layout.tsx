/** Кабинет всегда динамический — не пререндерим при build (экономит память на shared hosting). */
export const dynamic = 'force-dynamic';

export default function KabinetLayout({ children }: { children: React.ReactNode }) {
    return children;
}
