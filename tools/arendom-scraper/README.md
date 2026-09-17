# arendom.com scraper

Playwright-сервис для выгрузки карточек [arendom.com](https://arendom.com/) в `backend/var/import/arendom/`.

Сайт за Imunify360, поэтому обычный HTTP часто получает «One moment, please...». Каталог — CPT `kvartiry` (`/kvartiry/`, ~36 страниц). Скрапер проходит челлендж в headless Chromium, забирает список через WP REST и качает фото.

```bash
# 3 карточки для проверки
make scrape-arendom LIMIT=3

# вся база
make scrape-arendom

# импорт
make import-partner-listings OWNER=<userId> DRY_RUN=1
make import-partner-listings OWNER=<userId> LIMIT=10
make import-partner-listings OWNER=<userId>
```

Результат: `listings.json` + `images/<externalId>/`. Повторный запуск пропускает уже сохранённые `externalId`.
