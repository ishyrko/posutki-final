/** SSR-текст для главной: индексируемый контент над блоком преимуществ. */
export default function HomeSeoTextSection() {
  return (
    <div className="mb-12 md:mb-16 text-left">
      <h2 className="text-2xl md:text-3xl font-bold text-foreground font-display mb-4">
        Посуточная аренда квартир и загородных усадеб в Беларуси
      </h2>
      <div className="text-sm md:text-base text-muted-foreground leading-relaxed space-y-3">
        <p>
          <span className="font-medium text-foreground">Посутки.by</span> — сервис посуточной аренды
          жилья, где можно снять квартиру на сутки, а также или дом или усадьбу напрямую у владельцев. Актуальные
          объявления в Минске, Гродно, Бресте, Витебске, Гомеле, Могилёве и других городах Беларуси.
        </p>
        <p>
          Удобный поиск по городу, типу жилья и количеству гостей, понятные цены за сутки и фотографии в
          каждом объявлении. Разместите своё жильё бесплатно или найдите вариант для отдыха, командировки
          или путешествия по Беларуси.
        </p>
      </div>
    </div>
  );
}
