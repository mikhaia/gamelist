# Интеграция RAWG

## Область работы

RAWG используется только сервером Laravel в двух сценариях:

1. `GET /game/{catalogGame}` дополняет локальную игру метаданными, полными
   скриншотами и Steam App ID.
2. Фильтр каталога по жанру или платформе сначала показывает записи из
   `catalog_games`, затем запрашивает RAWG, объединяет результаты с локальными
   записями и повторно выводит уже локальную выдачу.

Браузер не обращается к `api.rawg.io` напрямую, поэтому API-ключ не попадает в
HTML или JavaScript. Обычный поиск только по названию продолжает использовать
HowLongToBeat; RAWG выбирается при наличии `genre` или `platform`.

## Схема `catalog_games`

| Поле | Тип | Назначение |
| --- | --- | --- |
| `hltb_id` | nullable unsigned bigint, unique | ID HowLongToBeat; nullable для RAWG-only игры |
| `rawg_id` | nullable unsigned bigint, unique | `games.results[].id` |
| `rawg_slug` | nullable string | slug игры RAWG |
| `cover_url` | nullable text | `background_image` используется только как обложка |
| `screenshots` | nullable JSON | HTTPS-ссылки на скриншоты |
| `genres` | nullable JSON | отображаемые названия жанров |
| `genre_slugs` | nullable JSON | RAWG slug для фильтра `genres` |
| `age_rating` | nullable string | `esrb_rating.name` |
| `platforms` | nullable JSON | отображаемые названия платформ |
| `platform_ids` | nullable JSON | числовые RAWG ID для фильтра `platforms` |
| `rawg_added` | nullable unsigned integer | популярность RAWG для сортировки `-added` |
| `steam_id` | nullable string | App ID из ссылки Steam |
| `rawg_synced_at` | nullable timestamp | время полной синхронизации detail-страницы |

`tags`, `background` и `movies` намеренно отсутствуют. Gameplay videos относятся
к платному уровню RAWG и в этой интеграции не запрашиваются.

## Конфигурация

```dotenv
RAWG_API_KEY=<secret>
RAWG_API_URL=https://api.rawg.io/api
RAWG_SYNC_TTL_DAYS=30
RAWG_SEARCH_TTL_HOURS=6
```

- `RAWG_SYNC_TTL_DAYS` ограничивает частоту полной синхронизации detail-страницы.
- `RAWG_SEARCH_TTL_HOURS` не позволяет повторять один и тот же RAWG-запрос
  фильтра при каждом открытии. Сами игры независимо от этого сохраняются в
  `catalog_games` и участвуют в последующих локальных поисках.
- при отсутствии ключа локальная выдача и detail-страница продолжают работать;
  внешний запрос возвращает контролируемую ошибку, а ключ не логируется.

## Полная синхронизация страницы игры

1. Для локальной игры выполняется точный поиск:

   ```http
   GET /games?search=<title>&search_precise=true&page_size=10&key=...
   ```

2. Совпадение выбирается только по нормализованному точному названию.
3. Для найденного `rawg_id` запрашиваются:

   ```http
   GET /games/<rawg_id>/screenshots?key=...&page_size=20
   GET /games/<rawg_id>/stores?key=...
   ```

4. Сохраняются уникальные HTTPS-скриншоты, жанры и их slug, платформы и их ID,
   возрастной рейтинг и Steam App ID. `background_image` не сохраняется в
   отдельном поле и исключается из галереи.
5. `rawg_synced_at` выставляется только после полной синхронизации. Результаты
   спискового поиска намеренно не выставляют этот timestamp, чтобы первое
   открытие detail-страницы всё равно загрузило полные скриншоты и магазины.

Сетевые ошибки, timeout, невалидный JSON и отсутствие точного совпадения
логируются, но не превращают страницу игры в `500`.

## Поиск по жанрам и платформам

Чипы detail-страницы ведут на URL вида:

```text
/search?genre=role-playing-games-rpg&genre_name=RPG
/search?platform=4&platform_name=PC
```

На странице поиска те же параметры доступны через выпадающие списки жанров и
платформ в основной поисковой строке. Базовые RAWG slug/ID всегда доступны, а
варианты из уже сохранённых игр автоматически добавляются из `catalog_games`.

Серверная последовательность:

1. `CatalogGameCache::paginate()` выполняет локальный `whereJsonContains` по
   `genre_slugs` и/или `platform_ids`; текстовый `q` можно применять вместе с
   фильтрами.
2. Клиент показывает локальные результаты без ожидания внешнего API.
3. `GET /catalog/search/rawg` запрашивает RAWG:

   ```http
   GET /games?genres=<slug>&platforms=<numeric-id>&ordering=-added&page_size=40&key=...
   ```

4. До 40 популярных результатов нормализуются и сохраняются в
   `catalog_games`.
5. Клиент повторяет локальный запрос и показывает объединённую выдачу.

Платформа передаётся RAWG только числовым ID: slug вроде `pc` для параметра
`platforms` не используется.

### Объединение RAWG и HLTB

- сначала ищется запись по ID соответствующего провайдера;
- если ID ещё нет, используется точное `normalized_title` записи другого
  провайдера;
- RAWG обновляет свои поля, не стирая время прохождения HLTB;
- HLTB обновляет свои поля, не стирая RAWG-метаданные;
- уникальные `hltb_id` и `rawg_id` защищают от повторного кеширования одной игры;
- RAWG-only игра сохраняется с `hltb_id = null` и может быть добавлена в список
  с корректным `catalog_game_id`.

## Отображение и атрибуция

- жанры и платформы на detail-странице являются ссылками-фильтрами;
- возрастной рейтинг выводится числовым ESRB-бейджем: `Everyone` → `6+`,
  `Everyone 10+` → `10+`, `Teen` → `13+`, `Mature` → `17+`,
  `Adults Only` → `18+`;
- скриншоты открываются в модальном окне поверх sticky header;
- footer содержит внешние backlink на RAWG и HowLongToBeat на всех страницах.

Blade экранирует подписи и URL. Сервис изображений принимает только абсолютные
HTTPS-ссылки.

## Тесты

Feature-тесты проверяют:

- точное detail-обогащение, slug/ID фильтров, ESRB и Steam App ID;
- локальную фильтрацию по жанру и платформе, в том числе одновременно;
- RAWG-запрос с genre slug и числовым platform ID;
- TTL одинакового фильтра;
- merge RAWG ↔ HLTB без дублей и потери полей;
- добавление RAWG-only игры в пользовательский список;
- кликабельные чипы, footer backlink и модальное окно скриншотов;
- отсутствие реальных RAWG-запросов из PHPUnit.

## Официальные ссылки

- [RAWG API documentation](https://api.rawg.io/docs/)
- [RAWG API plans and attribution requirements](https://rawg.io/apidocs)
