# Чек-лист задач: «Лаборатория нестабильных порталов»

> Основан на [specify.md](specify.md) и [plan.md](plan.md). Каждая задача атомарна и тестируема (проверяется вручную или через тест/команду) отдельно от других.

---

## Phase 1: Setup Workspace (инициализация /backend и /frontend)

- [x] Task 1.1: Создать корневую структуру монорепозитория (`/backend`, `/frontend`), убедиться что `specify.md`/`plan.md` остаются в корне.
- [x] Task 1.2: Инициализировать Laravel 11 проект в `/backend` (`laravel new` или `composer create-project`), проверить, что `php artisan serve` запускается без ошибок.
- [x] Task 1.3: Настроить `/backend/.env` для подключения к MySQL (`127.0.0.1:3306`, база `moxPortals`, логин/пароль `mysql`/`mysql`), проверить `php artisan migrate:status` без ошибок соединения.
- [x] Task 1.4: Создать базу `moxPortals` в локальном MySQL (если не существует) и убедиться, что Laravel успешно подключается (`php artisan db:show`).
- [x] Task 1.5: Настроить `phpunit.xml` для тестового окружения (отдельная тестовая БД или `DB_CONNECTION=mysql`/`:memory:` для тестов), проверить `php artisan test` (пустой прогон без тестов проходит).
- [x] Task 1.6: Инициализировать React + Vite проект в `/frontend` (`npm create vite@latest`), проверить `npm run dev` открывает стартовую страницу.
- [x] Task 1.7: Установить и настроить Tailwind CSS в `/frontend` (конфиг, директивы в `index.css`), проверить, что тестовый класс Tailwind применяется в браузере. *(Tailwind v4 через `@tailwindcss/vite` + `@import "tailwindcss"` в `index.css`, без отдельного `postcss.config.js`/`tailwind.config.js` — актуальный способ подключения для этой мажорной версии.)*
- [x] Task 1.8: Установить `axios`, `@tanstack/react-query`, `react-router-dom` в `/frontend`, проверить успешный `npm run build` без ошибок разрешения зависимостей.
- [x] Task 1.9: Настроить `.env`/`.env.example` во `/frontend` с переменной `VITE_API_URL`, указывающей на локальный backend (например, `http://127.0.0.1:8000/api`).
- [x] Task 1.10: Создать `.gitignore` для `/backend` (vendor, .env, storage/*.key) и `/frontend` (node_modules, dist, .env), закоммитить базовый скелет обоих проектов. Скелеты закоммичены (см. коммиты «бэк», «фронтенд» в истории git).

---

## Phase 2: Backend Domain & Business Logic (миграции, сервисы, контроллеры, edge cases)

- [x] Task 2.1: Создать миграцию `create_portals_table` с полями по [plan.md §1.2](plan.md) (`id` UUID, `name`, `destination_world`, `energy_level`, `stability`, `time_to_collapse`, `creatures_count`, `status` enum, timestamps) и индексом по `status`.
- [x] Task 2.2: Добавить CHECK-констрейнты на `energy_level` (1..100) и `stability` (0..1) в миграцию `portals`, проверить `php artisan migrate` выполняется без ошибок.
- [x] Task 2.3: Создать миграцию `create_portal_logs_table` (`id` UUID, `portal_id` FK, `action_type` enum, `description`, `previous_state` JSON, `new_state` JSON, `timestamp`), внешний ключ `onDelete('cascade')`, индекс `(portal_id, timestamp)`.
- [x] Task 2.4: Прогнать `php artisan migrate` на локальной БД `moxPortals`, проверить наличие обеих таблиц (`php artisan db:table portals`, `php artisan db:table portal_logs`).
- [x] Task 2.5: Создать Enum `PortalStatus` (`active`, `stabilized`, `closed`, `under_review`) в `app/Enums`.
- [x] Task 2.6: Создать Enum `ActionType` (`stabilize`, `close`, `dispatch_observer`, `mark_under_review`) в `app/Enums`.
- [x] Task 2.7: Создать Enum `RiskLevel` (`CRITICAL`, `HIGH`, `MEDIUM`, `LOW`) в `app/Enums`.
- [x] Task 2.8: Создать модель `Portal` с `HasUuids`, кастами полей (`energy_level` int, `stability` float, `status` → `PortalStatus`) и связью `hasMany(PortalLog::class)`.
- [x] Task 2.9: Создать модель `PortalLog` с кастами `previous_state`/`new_state` → array, связью `belongsTo(Portal::class)`.
- [x] Task 2.10: Реализовать `App\Services\RiskCalculator` с методами `score(Portal $portal): int` и `level(Portal $portal): RiskLevel` по формуле из [specify.md §3](specify.md).
- [x] Task 2.11: Вручную проверить `RiskCalculator` через `php artisan tinker` на 2-3 вручную подобранных наборах значений, сверить результат с формулой.
- [x] Task 2.12: Создать исключение `App\Exceptions\PortalActionException` с полями `errorCode`, `context` и методом `render()`, возвращающим JSON 422 по формату из [plan.md §4.2](plan.md).
- [x] Task 2.13: Реализовать `App\Services\PortalActionService::stabilize(Portal $portal)` — проверка BR-1 (запрет на `closed`), обновление `stability`/`energy_level`/`status`, возврат снапшотов `previous_state`/`new_state`.
- [x] Task 2.14: Реализовать `App\Services\PortalActionService::close(Portal $portal, bool $forceEvacuate)` — проверка BR-3 (`creatures_count > 0` без `force_evacuate`), обновление `status`, формирование `description` с упоминанием принудительной эвакуации при `force_evacuate=true`.
- [x] Task 2.15: Реализовать `App\Services\PortalActionService::dispatchObserver(Portal $portal)` — проверка BR-2 (запрет при `RiskLevel::CRITICAL` через `RiskCalculator`), увеличение `creatures_count`, установка `status = under_review`.
- [x] Task 2.16: Реализовать `App\Services\PortalActionService::markUnderReview(Portal $portal)` — проверка запрета на `closed` (аналог BR-1), установка `status = under_review` без изменения прочих полей.
- [x] Task 2.17: Реализовать общий метод `PortalActionService::perform(Portal $portal, ActionType $action, bool $forceEvacuate)`, оборачивающий вызов конкретного метода в `DB::transaction` с `lockForUpdate()` и созданием `PortalLog` при успехе.
- [x] Task 2.18: Создать `App\Http\Requests\PerformPortalActionRequest` с валидацией `action` (`in:stabilize,close,dispatch_observer,mark_under_review`) и `force_evacuate` (`boolean`, nullable).
- [x] Task 2.19: Создать `App\Http\Resources\PortalResource`, добавляющий вычисляемые `risk_score`/`risk_level` через `RiskCalculator` к полям модели `Portal`.
- [x] Task 2.20: Создать `App\Http\Resources\PortalLogResource`, отдающий поля `PortalLog` в формате из [plan.md §2.4](plan.md).
- [x] Task 2.21: Реализовать `PortalController::index()` — список порталов с фильтрами `status`/`risk_level`, сортировкой `sort`, пагинацией и блоком `summary` (агрегаты по полному набору, не по отфильтрованному).
- [x] Task 2.22: Реализовать `PortalController::show(Portal $portal)` — возврат одного портала через `PortalResource`, 404 при отсутствии (стандартный route model binding).
- [x] Task 2.23: Реализовать `PortalActionController::perform(PerformPortalActionRequest $request, Portal $portal)` — вызов `PortalActionService::perform()`, возврат `{ data, log }` при успехе.
- [x] Task 2.24: Реализовать `PortalLogController::index()` — список логов с фильтрами `portal_id`/`action_type`, сортировкой `-timestamp`, пагинацией.
- [x] Task 2.25: Зарегистрировать маршруты `GET /api/portals`, `GET /api/portals/{portal}`, `POST /api/portals/{portal}/action`, `GET /api/logs` в `routes/api.php`.
- [x] Task 2.26: Проверить все 4 маршрута вручную через `curl`/Postman на реальной БД (happy path каждого действия проходит, возвращает корректный JSON).

---

## Phase 3: Backend Automated Tests (PHPUnit)

- [x] Task 3.1: Написать `Unit/RiskCalculatorTest`: проверка `score()` на границах 0/25/50/75/100 и корректность бонуса `+20` при `time_to_collapse < 15` (включая проверку, что при `time_to_collapse = 15` бонус НЕ применяется).
- [x] Task 3.2: Написать `Unit/RiskCalculatorTest::test_level_mapping`: проверка соответствия `score → level` для всех 4 уровней (CRITICAL/HIGH/MEDIUM/LOW) на граничных и промежуточных значениях.
- [x] Task 3.3: Написать Feature-тест: успешный `stabilize` на портале со статусом `active` — проверка `stability = 0.95`, `energy_level` уменьшен на 10%, `status = stabilized`, создана запись `PortalLog` с `action_type = stabilize`.
- [x] Task 3.4: Написать Feature-тест: `stabilize` на портале со статусом `closed` — проверка HTTP 422, `error_code = PORTAL_ALREADY_CLOSED`, портал не изменился в БД, запись в `portal_logs` не создана.
- [x] Task 3.5: Написать Feature-тест: успешный `close` на портале с `creatures_count = 0` — проверка `status = closed` без необходимости `force_evacuate`, создана запись лога.
- [x] Task 3.6: Написать Feature-тест: `close` на портале с `creatures_count > 0` без `force_evacuate` — проверка HTTP 422, `error_code = EVACUATION_REQUIRED`, портал не изменён, лог не создан.
- [x] Task 3.7: Написать Feature-тест: `close` на портале с `creatures_count > 0` и `force_evacuate=true` — проверка `status = closed`, `description` лога содержит упоминание принудительной эвакуации.
- [x] Task 3.8: Написать Feature-тест: успешный `dispatch_observer` на портале с risk level, отличным от CRITICAL — проверка `creatures_count += 1`, `status = under_review`, создана запись лога.
- [x] Task 3.9: Написать Feature-тест: `dispatch_observer` на портале с risk level CRITICAL — проверка HTTP 422, `error_code = RISK_TOO_HIGH_FOR_OBSERVER`, портал не изменён, лог не создан.
- [x] Task 3.10: Написать Feature-тест: успешный `mark_under_review` на портале со статусом, отличным от `closed` — проверка изменения только `status`, прочие поля не тронуты.
- [x] Task 3.11: Написать Feature-тест: `mark_under_review` на портале со статусом `closed` — проверка HTTP 422 с соответствующим `error_code`.
- [x] Task 3.12: Написать Feature-тест: атомарность действия — при нарушении бизнес-правила количество записей в `portal_logs` до и после запроса совпадает, а поля портала в БД идентичны (полный snapshot сравнение).
- [x] Task 3.13: Написать Feature-тест на `GET /api/portals`: фильтр по `status` возвращает только соответствующие записи, `summary.by_status` при этом отражает полный набор (не зависит от фильтра).
- [x] Task 3.14: Написать Feature-тест на `GET /api/portals`: фильтр по `risk_level` корректно фильтрует данные на основе вычисленного значения (не хранимого поля).
- [x] Task 3.15: Написать Feature-тест на `GET /api/portals`: проверка корректности `summary.avg_risk_score_active`, `summary.creatures_total_open` и `summary.top_risky_active` (не более 5 элементов, отсортированы по убыванию риска) на подготовленном наборе данных.
- [x] Task 3.16: Написать Feature-тест на `GET /api/logs`: сортировка строго по `timestamp desc`, фильтр по `portal_id` возвращает только записи нужного портала.
- [x] Task 3.17: Написать Feature-тест на `GET /api/portals/{id}` с несуществующим UUID — проверка HTTP 404.
- [x] Task 3.18: Написать Feature-тест на `POST /api/portals/{id}/action` с некорректным значением `action` (не входящим в enum) — проверка HTTP 422 в формате стандартной Laravel-валидации (`errors.action`).
- [x] Task 3.19: Прогнать полный набор тестов (`php artisan test`), убедиться, что все тесты зелёные и покрывают BR-1/BR-2/BR-3 (US-9, AC-9.1–9.3).

---

## Phase 4: Frontend UI Core (интерфейс, таблицы, карточки, фильтры)

- [x] Task 4.1: Настроить React Router с маршрутами `/` (порталы), `/logs`, `/ai-worklog` и общий Layout с верхним таб-баром навигации. *(Реализовано с сознательным отклонением: по явному ТЗ шапка переключает только 2 вкладки — «Панель управления» и «AI Worklog»; журнал событий отображается секцией на той же странице `/`, отдельный `/logs` не создавался.)*
- [x] Task 4.2: Создать компонент `RiskBadge` (цветовая индикация CRITICAL/HIGH/MEDIUM/LOW через Tailwind), проверить визуально на 4 тестовых значениях.
- [x] Task 4.3: Создать компонент `PortalCard` (статичная верстка, принимает объект портала как проп, отображает все поля из [specify.md §2.1](specify.md) + `RiskBadge`), проверить рендер на моковых данных.
- [x] Task 4.4: Создать компонент `PortalList` (рендерит список `PortalCard` по массиву порталов), проверить рендер на моковом массиве из 3-5 порталов.
- [x] Task 4.5: Реализовать визуальное выделение карточек с `risk_level = CRITICAL` (AC-1.4) — отдельный класс/бордер/фон в `PortalCard`.
- [x] Task 4.6: Создать компонент `PortalFilters` (select по `status`, select по `risk_level`), проверить, что выбор опций вызывает переданный `onChange` с корректными значениями (на моках, без API).
- [x] Task 4.7: Создать компонент `DashboardSummary` (принимает объект `summary` как проп, отображает счётчики по статусам/риск-уровням, средний risk score, топ-5 рискованных порталов), проверить рендер на моковом `summary`.
- [x] Task 4.8: Создать компонент `ActionLogsTable` (таблица логов с колонками `action_type`, `description`, `timestamp`), проверить рендер на моковом массиве логов.
- [x] Task 4.9: Добавить в `ActionLogsTable` раскрывающуюся строку (accordion) с diff `previous_state` vs `new_state` (AC-6.2), проверить визуально на моковой записи с различающимися состояниями.
- [x] Task 4.10: Создать компонент `ToastProvider`/`useToast` (контекст, стек уведомлений, автоскрытие через таймаут, ручное закрытие), проверить показ/скрытие тостов вручную (кнопка-триггер в деве).
- [x] Task 4.11: Создать заготовку `ActionModal` (открытие/закрытие, заголовок с названием действия, кнопки "Подтвердить"/"Отмена"), без интеграции с API — проверить открытие для каждого из 4 действий на моковом портале. *(Сразу реализована полная версия с интеграцией — см. Phase 5.)*

---

## Phase 5: Frontend Integration & State (связка с API, модальные окна, обработка 422)

- [x] Task 5.1: Создать `src/api/client.js` — экземпляр `axios` с `baseURL` из `VITE_API_URL` и response-интерцептором, нормализующим ошибки 422 (`business_rule` vs `validation`) по формату из [plan.md §6.1](plan.md).
- [x] Task 5.2: Создать `src/api/portals.js` с функциями `fetchPortals(filters)`, `fetchPortal(id)`, `performAction(id, payload)`, проверить каждую вызовом к запущенному backend (happy path).
- [x] Task 5.3: Создать `src/api/logs.js` с функцией `fetchLogs(filters)`, проверить вызовом к запущенному backend.
- [x] Task 5.4: Настроить `QueryClientProvider` (React Query) в корне приложения (`main.jsx`), проверить DevTools React Query отображают активный клиент.
- [x] Task 5.5: Создать хук `usePortals(filters)` на `useQuery(['portals', filters], …)`, подключить к `PortalsPage`, заменив моковые данные на реальные из API.
- [x] Task 5.6: Создать хук `usePortal(id)` на `useQuery(['portal', id], …)` для детального просмотра портала (если используется отдельная страница/панель деталей). Хук создан ([hooks/usePortal.js](frontend/src/hooks/usePortal.js)) и готов к использованию; в текущем UI отдельной страницы деталей нет (вся информация доступна в карточке/строке таблицы), поэтому хук пока не подключён ни к одному экрану.
- [x] Task 5.7: Создать хук `useLogs(filters)` на `useQuery(['logs', filters], …)`, подключить к `LogsPage`, заменив моковые данные на реальные.
- [x] Task 5.8: Подключить реальный `summary` из ответа `usePortals` к `DashboardSummary` на `PortalsPage`, убедиться, что отображаются реальные агрегаты с backend.
- [x] Task 5.9: Подключить `PortalFilters` к состоянию фильтров `PortalsPage`, проверить, что изменение фильтра перезапускает `useQuery` с новыми параметрами и обновляет список.
- [x] Task 5.10: Создать хук `usePortalAction()` на `useMutation`, вызывающий `performAction`, с `onSuccess` → `invalidateQueries(['portals'])` и `invalidateQueries(['logs'])`.
- [x] Task 5.11: Подключить `usePortalAction` к `ActionModal` — сабмит формы вызывает мутацию с `action` и `force_evacuate`.
- [x] Task 5.12: Реализовать в `ActionModal` условный блок `ForceEvacuateWarning` для действия `close`, когда `portal.creatures_count > 0` (чекбокс подтверждения, включающий `force_evacuate=true` в payload).
- [x] Task 5.13: Реализовать в `ActionModal` блокировку кнопки подтверждения для действия `dispatch_observer`, когда `portal.risk_level === 'CRITICAL'` (UX-дублирование BR-2), с поясняющим текстом.
- [x] Task 5.14: Реализовать вызов `showToast` при успешной мутации (`onSuccess`) с сообщением об успешном выполнении действия.
- [x] Task 5.15: Реализовать вызов `showToast` при ошибке мутации (`onError`) с текстом `error.message` и, для `business_rule`, отображением `error.errorCode`.
- [x] Task 5.16: Проверить вручную: `ActionModal` не закрывается при получении 422 (портал/форма остаются видны для повторной попытки). *(Гарантировано конструкцией кода: `onClose` вызывается только в `onSuccess` мутации.)*
- [x] Task 5.17: Проверить вручную (после Phase 6, seed-demo): попытка `stabilize` на закрытом портале через UI — появляется Toast с `PORTAL_ALREADY_CLOSED`, состояние портала в списке не меняется. *(В финальном UI такие действия дополнительно блокируются на уровне кнопок для закрытых порталов — недостижимое состояние подтверждено и на backend через curl.)*
- [x] Task 5.18: Проверить вручную: попытка `close` без `force_evacuate` на портале с существами — Toast `EVACUATION_REQUIRED`, автоматическое раскрытие `ForceEvacuateWarning` в модалке. Проверено вручную в браузере на демо-портале «Звенящая Арка» (3 существа).
- [x] Task 5.19: Проверить вручную: попытка `dispatch_observer` на CRITICAL-портале — кнопка задизейблена на клиенте; при обходе через прямой API-вызов (curl) backend всё равно возвращает 422. Проверено: UI блокирует, curl-запрос вернул `422 RISK_TOO_HIGH_FOR_OBSERVER`.
- [x] Task 5.20: Проверить вручную: после успешного действия любого типа список порталов и журнал логов на странице обновляются без ручного релоада страницы. Проверено на `close` (force_evacuate) и `stabilize` — сводка, карточки и журнал обновились мгновенно.

---

## Phase 6: AI Worklog Component & Demo Seeder

- [x] Task 6.1: Создать `App\Console\Seeders\DemoPortalSeeder` (или `DatabaseSeeder`-класс), генерирующий 5 порталов, покрывающих все edge cases из [plan.md §2.5](plan.md) (CRITICAL-риск, `closed`-статус, `creatures_count > 0`, безопасный к закрытию, "здоровый" LOW-риск). *(Выполнено вне очереди фаз — потребовалось для рабочей кнопки "Сбросить к демо-состояниям" из Phase 4/5.)*
- [x] Task 6.2: Реализовать `DemoSeedController::run()` — транзакционная очистка `portal_logs`/`portals` и вызов сидера, с guard `abort_unless(app()->environment(['local','testing']), 403)`.
- [x] Task 6.3: Зарегистрировать маршрут `POST /api/portals/seed-demo`, проверить вручную вызов через curl в `local`-окружении — БД пересоздаётся с 5 портами. Проверено: `curl -X POST .../seed-demo` → `{"message":"Демо-данные пересозданы.","portals_created":5}`.
- [x] Task 6.4: Написать Feature-тест `DemoSeedTest`: вызов в `testing`-окружении возвращает 200 и создаёт ожидаемое количество порталов с нужными характеристиками (по одному на каждый edge case).
- [x] Task 6.5: Написать Feature-тест `DemoSeedTest`: вызов при замоканном окружении `production` возвращает HTTP 403.
- [x] Task 6.6: Добавить в frontend (например, кнопку в `PortalsPage` или отдельный dev-only UI) вызов `POST /api/portals/seed-demo` для удобства ручного тестирования UI. Реализовано как кнопка «Сбросить к демо-состояниям» в `Header` с диалогом подтверждения.
- [x] Task 6.7: Создать `frontend/src/data/ai-worklog.json` со структурой записей (`stage`, `prompt`, `ai_mistakes`, `manual_overrides`, `timestamp`) по формату из [specify.md §7](specify.md), заполнить реальными этапами разработки текущего проекта.
- [x] Task 6.8: Создать компонент `AIWorklogPage`, читающий `ai-worklog.json` и рендерящий хронологический таймлайн этапов.
- [x] Task 6.9: Реализовать в `AIWorklogPage` отображение `ai_mistakes`/`manual_overrides` как списков, с плейсхолдером ("Ошибок не зафиксировано" / "Правок не было") для пустых массивов (AC-8.4).
- [x] Task 6.10: Проверить вручную: вкладка `/ai-worklog` отображает все записи из JSON без ошибок консоли, пустые секции отображаются корректно (не ломают вёрстку). Проверено в браузере, консоль чистая.

---

## Phase 7: Documentation & Verification Checklist

- [x] Task 7.1: Создать `backend/README.md` с инструкцией запуска (установка зависимостей, настройка `.env`, миграции, запуск `php artisan serve`, запуск тестов).
- [x] Task 7.2: Создать `frontend/README.md` с инструкцией запуска (установка зависимостей, настройка `.env`, `npm run dev`, `npm run build`).
- [x] Task 7.3: Создать корневой `README.md` с общим описанием проекта, ссылками на `specify.md`/`plan.md`/`tasks.md` и порядком запуска backend+frontend вместе.
- [x] Task 7.4: Прогнать полный набор PHPUnit-тестов (`php artisan test`) и зафиксировать в README backend команду и ожидаемый результат ("все тесты зелёные"). Финальный прогон: **39 тестов, 127 assertions — все зелёные**.
- [x] Task 7.5: Пройти вручную по всем User Stories из [specify.md §8](specify.md) (US-1…US-9) на живом стенде (backend + frontend + seed-demo), отметить соответствие каждому AC. Пройдено через встроенный браузер: список с риском (US-1), stabilize (US-2), close с/без эвакуации (US-3), dispatch_observer с блокировкой CRITICAL (US-4), mark_under_review (US-5), журнал (US-6), дашборд (US-7), AI Worklog (US-8), защита правил на уровне API (US-9).
- [x] Task 7.6: Проверить вручную все 3 бизнес-правила (BR-1, BR-2, BR-3) через UI end-to-end: получение 422, корректный Toast, отсутствие изменений состояния при отказе. Проверено: BR-1/BR-2 блокируются на уровне UI (кнопки задизейблены) и дополнительно подтверждены через curl (422 с корректным `error_code`); BR-3 подтверждена end-to-end через реальный клик в UI с Toast-уведомлением об успехе после эвакуации.
- [x] Task 7.7: Проверить вручную корректность Dashboard Stats (`summary`) на демо-данных — сверить показатели на экране с ожидаемыми значениями по данным `seed-demo`. Сверено: после `seed-demo` — 4 открытых/1 критический/1 закрытый портал, после серии действий сводка (существа, средний риск) пересчиталась корректно и мгновенно.
- [x] Task 7.8: Проверить вручную журнал событий (объединён с панелью управления на `/`, см. примечание к Task 4.1) — записи присутствуют для всех выполненных за сессию действий, сортировка по времени верна, diff `previous_state`/`new_state` отображается корректно.
- [x] Task 7.9: Проверить вручную вкладку AI Worklog — данные отображаются, структура соответствует расширенному ТЗ (инструменты, время, токены, таблица этапов, промпты, решения, ошибки AI, чек-лист), консоль браузера чистая.
- [x] Task 7.10: Финальный прогон: выполнить `seed-demo`, пройти по одному разу каждый из 4 действий на подходящих порталах, убедиться в отсутствии ошибок в консоли браузера и логах backend (`storage/logs/laravel.log`). Выполнено; по пути обнаружена и исправлена реальная проблема — `PortalActionException` логировал ожидаемые 422-отказы как `ERROR` (добавлен `report(): true`), после исправления лог чист (0 ERROR).
