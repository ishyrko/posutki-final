export function CatalogPageFallback() {
  return (
    <div className="min-h-screen bg-background pt-10 pb-16 flex items-center justify-center">
      <div
        className="h-9 w-9 animate-spin rounded-full border-2 border-primary border-t-transparent"
        role="status"
        aria-label="Загрузка каталога"
      />
    </div>
  );
}
