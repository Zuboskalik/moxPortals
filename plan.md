# Архитектурный план: «Лаборатория нестабильных порталов»

> Основан на [specify.md](specify.md). Технический стек: Laravel 11 + MySQL + PHPUnit (backend), React (Vite) + Tailwind + Axios + React Query (frontend).
>
> **Примечание по кодам ошибок:** в `specify.md` бизнес-правила описаны с HTTP 409. По требованию текущего плана используется **HTTP 422 Unprocessable Entity** для всех нарушений бизнес-правил (BR-1/BR-2/BR-3) — это финальное решение, используемое в реализации; см. раздел 4.

---

## 0. Структура монорепозитория

```
/backend                  # Laravel 11 API
  app/
    Enums/                # PortalStatus, ActionType, RiskLevel
    Models/                # Portal, PortalLog
    Services/              # RiskCalculator, PortalActionService
    Exceptions/            # PortalActionException (+ handler -> 422)
    Http/
      Controllers/Api/     # PortalController, PortalActionController, PortalLogController, DemoSeedController
      Requests/            # PerformPortalActionRequest
      Resources/           # PortalResource, PortalLogResource
  database/
    migrations/            # create_portals_table, create_portal_logs_table
    seeders/                # DemoPortalSeeder
  routes/api.php
  tests/
    Unit/                  # RiskCalculatorTest
    Feature/                # PortalActionTest, PortalListTest, DemoSeedTest

/frontend                 # React + Vite
  src/
    api/                   # axios instance, portals.js, logs.js
    hooks/                 # useQuery/useMutation обёртки (React Query)
    components/
      portals/             # PortalList, PortalCard, RiskBadge, PortalFilters
      actions/              # ActionModal, ForceEvacuateWarning
      logs/                  # ActionLogsTable
      dashboard/             # DashboardSummary
      worklog/               # AIWorklogPage
      common/                # Toast/ToastProvider, LoadingSpinner, ErrorBoundary
    data/                   # ai-worklog.json
    pages/                  # PortalsPage, LogsPage, DashboardPage, AIWorklogPage
    router.jsx
    main.jsx

specify.md
plan.md
```

---

## 1. Схема базы данных (миграции)

### 1.1 Локальное окружение MySQL

`.env` backend:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moxPortals
DB_USERNAME=mysql
DB_PASSWORD=mysql
```

### 1.2 Таблица `portals`

Первичный ключ — `id` (UUID, `char(36)`), т.к. `specify.md` определяет `id` как UUID/string.

```php
Schema::create('portals', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('destination_world');
    $table->unsignedTinyInteger('energy_level');       // 1..100, CHECK на уровне приложения + БД (см. ниже)
    $table->decimal('stability', 3, 2);                 // 0.00..1.00
    $table->unsignedInteger('time_to_collapse');        // минуты, >= 0
    $table->unsignedInteger('creatures_count')->default(0);
    $table->enum('status', ['active', 'stabilized', 'closed', 'under_review'])
          ->default('active');
    $table->timestamps();

    $table->index('status');
});

// Доп. CHECK-констрейнты (MySQL 8+, через raw statement в той же миграции):
// ALTER TABLE portals ADD CONSTRAINT chk_energy_level CHECK (energy_level BETWEEN 1 AND 100);
// ALTER TABLE portals ADD CONSTRAINT chk_stability CHECK (stability >= 0 AND stability <= 1);
```

**Обоснование ограничений:** диапазоны из `specify.md` §2.1 дублируются на уровне БД (CHECK) как последний рубеж защиты, но основная валидация — в Laravel FormRequest/Model (см. §4).

### 1.3 Таблица `portal_logs`

```php
Schema::create('portal_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('portal_id');
    $table->enum('action_type', [
        'stabilize', 'close', 'dispatch_observer', 'mark_under_review',
    ]);
    $table->string('description');
    $table->json('previous_state');
    $table->json('new_state');
    $table->timestamp('timestamp')->useCurrent();

    $table->foreign('portal_id')->references('id')->on('portals')
          ->onDelete('cascade');
    $table->index(['portal_id', 'timestamp']);
});
```

**Примечание:** внешний ключ `onDelete('cascade')` — техническая необходимость (целостность БД); журнал по бизнес-правилам append-only (AC-6.3) и не должен удаляться/редактироваться через прикладной код в принципе — портал в норме никогда не удаляется физически (нет такого действия в спецификации).

### 1.4 Модели

- `App\Models\Portal`
  - `casts`: `energy_level` → int, `stability` → float, `status` → enum `PortalStatus`.
  - `HasUuids` (Laravel trait) для генерации UUID при создании.
  - accessor `riskScore()` / `riskLevel()` — **не сохраняются**, вычисляются через `RiskCalculator` (см. §1.5).
  - `hasMany(PortalLog::class)`.
- `App\Models\PortalLog`
  - `casts`: `previous_state` → array, `new_state` → array.
  - `belongsTo(Portal::class)`.
  - модель read-only на уровне приложения: не используется `update()`/`delete()` ни в одном сервисе.

### 1.5 Сервис расчёта риска (`app/Services/RiskCalculator.php`)

```php
final class RiskCalculator
{
    public function score(Portal $portal): int
    {
        $score = $portal->energy_level * 0.4
               + (1 - $portal->stability) * 40
               + ($portal->time_to_collapse < 15 ? 20 : 0);

        return (int) round(min(100, max(0, $score)));
    }

    public function level(Portal $portal): RiskLevel
    {
        $score = $this->score($portal);

        return match (true) {
            $score >= 75 => RiskLevel::CRITICAL,
            $score >= 50 => RiskLevel::HIGH,
            $score >= 25 => RiskLevel::MEDIUM,
            default      => RiskLevel::LOW,
        };
    }
}
```

Используется и в API-ресурсах (для отдачи `risk_score`/`risk_level`), и в `PortalActionService` (для проверки BR-2) — **единая точка вычисления**, чтобы не разошлись значения в разных местах (AC-4.4).

---

## 2. REST API Endpoints

Базовый префикс: `/api`. Формат ответов — JSON (`Accept: application/json` обязателен, `App\Exceptions\Handler` настроен на JSON-ответы для `api/*`).

### 2.1 `GET /api/portals`

Список порталов с фильтрами и сводкой.

**Query-параметры:**
| Параметр | Тип | Описание |
|---|---|---|
| `status` | string | фильтр по `status` (`active`, `stabilized`, `closed`, `under_review`) |
| `risk_level` | string | фильтр по вычисленному `risk_level` (`CRITICAL`, `HIGH`, `MEDIUM`, `LOW`) |
| `sort` | string | `risk_score`, `-risk_score`, `name`, `-name`, `time_to_collapse`, `-time_to_collapse` (по умолчанию `-risk_score`) |
| `per_page` | int | пагинация, по умолчанию 20 |

**Ответ 200:**
```json
{
  "data": [
    {
      "id": "…-uuid",
      "name": "Врата Пепла",
      "destination_world": "Кинжарис",
      "energy_level": 82,
      "stability": 0.31,
      "time_to_collapse": 8,
      "creatures_count": 2,
      "status": "active",
      "risk_score": 79,
      "risk_level": "CRITICAL",
      "created_at": "…",
      "updated_at": "…"
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 12 },
  "summary": {
    "by_status": { "active": 5, "stabilized": 3, "closed": 2, "under_review": 2 },
    "by_risk_level": { "CRITICAL": 2, "HIGH": 4, "MEDIUM": 4, "LOW": 2 },
    "avg_risk_score_active": 54.3,
    "creatures_total_open": 7,
    "top_risky_active": [ /* до 5 объектов Portal (как выше), status in [active, under_review] */ ],
    "logs_last_24h": 14
  }
}
```

`summary` — реализация Dashboard Stats (§6 specify.md, US-7) отдаётся тем же эндпоинтом, чтобы фронтенду не требовался отдельный запрос для базовой страницы списка. Фильтры (`status`, `risk_level`) применяются только к `data`/`meta`, `summary` всегда считается по **полному** набору порталов (независимо от фильтра), чтобы отражать общую картину лаборатории.

### 2.2 `GET /api/portals/{id}`

**Ответ 200:** объект портала (как в `data[]` выше), без обёртки `meta`/`summary`.
**Ответ 404:** портал не найден:
```json
{ "message": "Портал не найден." }
```

### 2.3 `POST /api/portals/{id}/action`

**Body:**
```json
{ "action": "stabilize", "force_evacuate": false }
```

| Поле | Тип | Обязательность |
|---|---|---|
| `action` | string, одно из: `stabilize`, `close`, `dispatch_observer`, `mark_under_review` | обязательно |
| `force_evacuate` | boolean | опционально, по умолчанию `false`; используется только для `close` |

Валидация формата запроса — `PerformPortalActionRequest` (422 при отсутствии/некорректном значении `action`, см. §4.1).
Бизнес-правила (BR-1/BR-2/BR-3) проверяются в `PortalActionService::perform()` **внутри транзакции** (`DB::transaction`):
1. Загрузить портал (`lockForUpdate()` — защита от гонок при параллельных действиях).
2. Проверить применимость правила для данного `action`.
3. При нарушении — бросить `PortalActionException` (доменная, см. §4) → перехватывается `Handler` → 422, **без записи в лог, без изменения портала** (транзакция откатывается автоматически, так как исключение выбрасывается до `save()`).
4. При успехе: снять снимок `previous_state` (сериализованный портал **до** изменений, включая на тот момент `risk_score`/`risk_level`), применить изменения, сохранить, снять `new_state`, создать `PortalLog`, закоммитить транзакцию.

**Ответ 200 (успех):**
```json
{
  "data": { /* обновлённый Portal, как в GET /api/portals/{id} */ },
  "log": { /* созданная запись PortalLog */ }
}
```

**Ответ 422 (нарушение бизнес-правила):** см. §4.2.

### 2.4 `GET /api/logs`

**Query-параметры:**
| Параметр | Тип | Описание |
|---|---|---|
| `portal_id` | uuid | фильтр по конкретному порталу (для US-6) |
| `action_type` | string | фильтр по типу действия |
| `per_page` | int | пагинация, по умолчанию 20 |

Сортировка — всегда по `timestamp desc` (AC-6.1), без параметра `sort`.

**Ответ 200:**
```json
{
  "data": [
    {
      "id": "…-uuid",
      "portal_id": "…-uuid",
      "action_type": "close",
      "description": "Портал закрыт с принудительной эвакуацией (2 существа).",
      "previous_state": { /* snapshot Portal */ },
      "new_state": { /* snapshot Portal */ },
      "timestamp": "2026-09-17T10:15:00Z"
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 34 }
}
```

### 2.5 `POST /api/portals/seed-demo`

Сбрасывает и наполняет БД демо-данными для проверки всех обязательных состояний (edge cases из §5 specify.md). Доступен **только в окружениях `local`/`testing`** (guard в контроллере: `abort_unless(app()->environment(['local', 'testing']), 403)`), чтобы исключить случайный вызов в проде.

**Действие:**
1. `DB::transaction`: `PortalLog::query()->delete()` → `Portal::query()->delete()`.
2. Запуск `DemoPortalSeeder`, создающего заведомо разнообразные состояния, покрывающие:
   - портал `CRITICAL` risk (для проверки BR-2 — запрет `dispatch_observer`);
   - портал `status = closed` (для проверки BR-1 — запрет `stabilize`/`mark_under_review`);
   - портал с `creatures_count > 0` и статусом, отличным от `closed` (для проверки BR-3 — запрет `close` без `force_evacuate`);
   - портал с `creatures_count = 0`, готовый к безопасному `close`;
   - портал `LOW` risk, полностью «здоровый», для контрольного сценария без ошибок.

**Ответ 200:**
```json
{
  "message": "Демо-данные пересозданы.",
  "portals_created": 5
}
```

**Ответ 403** (если окружение `production`):
```json
{ "message": "Недоступно в этом окружении." }
```

---

## 3. Маршруты (`routes/api.php`)

```php
Route::get('/portals', [PortalController::class, 'index']);
Route::get('/portals/{portal}', [PortalController::class, 'show']);
Route::post('/portals/{portal}/action', [PortalActionController::class, 'perform']);
Route::post('/portals/seed-demo', [DemoSeedController::class, 'run']);

Route::get('/logs', [PortalLogController::class, 'index']);
```

`{portal}` — route model binding по UUID; несуществующий id → автоматический 404 от Laravel (JSON, т.к. запрос идёт через `/api/*`).

---

## 4. Формат ответов при нарушении бизнес-правил (HTTP 422)

### 4.1 Ошибки валидации формата запроса (`PerformPortalActionRequest`)

Стандартный формат Laravel для `422` от `FormRequest` — используется как есть, без переопределения, чтобы не расходиться с конвенцией фреймворка:

```json
{
  "message": "The action field is required.",
  "errors": {
    "action": ["The action field is required."]
  }
}
```

### 4.2 Ошибки бизнес-правил (доменные, `PortalActionException`)

Единый формат — **отличается** от стандартного Laravel-формата валидации явным полем `error_code`, чтобы фронтенд мог различать «невалидный запрос» и «запрос валиден, но правило домена запрещает действие»:

```json
{
  "message": "Портал уже закрыт и не может быть стабилизирован.",
  "error_code": "PORTAL_ALREADY_CLOSED",
  "context": {
    "portal_id": "…-uuid",
    "current_status": "closed"
  }
}
```

**Таблица кодов** (соответствие §5 specify.md):

| `error_code` | Правило | HTTP | Пример `message` |
|---|---|---|---|
| `PORTAL_ALREADY_CLOSED` | BR-1 (и запрет `mark_under_review` на закрытом, US-5 AC-5.3) | 422 | «Портал уже закрыт и не может быть стабилизирован / переведён под наблюдение.» |
| `RISK_TOO_HIGH_FOR_OBSERVER` | BR-2 | 422 | «Риск CRITICAL слишком высок для отправки наблюдателя.» |
| `EVACUATION_REQUIRED` | BR-3 | 422 | «В портале остались существа (N). Подтвердите принудительную эвакуацию (force_evacuate=true).» |

Реализация:
```php
final class PortalActionException extends \RuntimeException
{
    public function __construct(
        private readonly string $errorCode,
        string $message,
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message'    => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context'    => $this->context,
        ], 422);
    }
}
```

`render()` на самом исключении — Laravel 11 подхватывает его автоматически без регистрации в `bootstrap/app.php`/`Handler`, что упрощает добавление новых доменных ошибок в будущем без правки центрального обработчика.

### 4.3 Атомарность (AC-9.3)

Так как `PortalActionException` выбрасывается **до** любого `save()`/`create()` внутри `DB::transaction`, откат гарантирован фреймворком — отдельного `catch`+`rollback` не требуется, что также покрывается Feature-тестами (`PortalActionTest`, см. §8).

---

## 5. Архитектура Frontend

### 5.1 Слой данных

- **`src/api/client.js`** — единый `axios.create({ baseURL: import.meta.env.VITE_API_URL })`, с interceptor'ом ответа, вытаскивающим `error_code`/`message`/`errors` из тела 422-ответа в единый формат для остального приложения.
- **`src/api/portals.js`** — `fetchPortals(filters)`, `fetchPortal(id)`, `performAction(id, { action, force_evacuate })`, `seedDemo()`.
- **`src/api/logs.js`** — `fetchLogs(filters)`.
- **React Query**: `useQuery(['portals', filters], …)`, `useQuery(['portal', id], …)`, `useQuery(['logs', filters], …)`, `useMutation` для `performAction`/`seedDemo` с `onSuccess: invalidateQueries(['portals'])` и `['logs']` (действие меняет и список, и журнал).

### 5.2 Компоненты

```
PortalsPage
 ├─ DashboardSummary          — читает summary из того же useQuery(['portals', filters])
 ├─ PortalFilters             — status / risk_level select'ы, обновляют filters state
 └─ PortalList
     └─ PortalCard[]           — на каждой: RiskBadge, кнопки действий → открывают ActionModal

ActionModal
 ├─ пропсы: portal, action, onClose
 ├─ если action === 'close' && portal.creatures_count > 0 → рендерит ForceEvacuateWarning
 │    (чекбокс "Подтверждаю принудительную эвакуацию" → включает force_evacuate=true в mutation)
 ├─ если action === 'dispatch_observer' && portal.risk_level === 'CRITICAL'
 │    → кнопка подтверждения задизейблена, поясняющий текст (UX-дублирование BR-2, финальная проверка всё равно на backend)
 └─ на submit → useMutation(performAction) → при 422 не закрывает модалку, показывает Toast + инлайн-сообщение с context

LogsPage
 └─ ActionLogsTable
     ├─ фильтр по portal_id (опционально, из query-параметра при переходе "посмотреть лог портала")
     └─ строка раскрывается (accordion) → diff previous_state vs new_state (AC-6.2)

AIWorklogPage                 — отдельная вкладка в навигации (React Router route "/ai-worklog")
 └─ читает статический src/data/ai-worklog.json, рендерит таймлайн этапов
     (пустые ai_mistakes/manual_overrides → плейсхолдер "Ошибок не зафиксировано" / "Правок не было", AC-8.4)
```

**Общие/системные:**
- `ToastProvider` (контекст + рендер в корне `App`) — единая точка показа уведомлений (см. §6).
- `ErrorBoundary` — верхнеуровневый, на случай непойманных ошибок рендера (не связан с 422-обработкой).

### 5.3 Роутинг

```
/                → PortalsPage (список + фильтры + сводка)
/logs            → LogsPage
/ai-worklog      → AIWorklogPage
```

Навигация — простой верхний таб-бар (Tailwind), три вкладки, соответствующие требованию «AIWorklogPage — отдельная вкладка».

---

## 6. Обработка ошибок на фронтенде (Toast при 422)

### 6.1 Axios interceptor

```js
// src/api/client.js
client.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status;
    const body = error.response?.data;

    if (status === 422) {
      return Promise.reject({
        kind: body?.error_code ? 'business_rule' : 'validation',
        errorCode: body?.error_code ?? null,
        message: body?.message ?? 'Действие невозможно.',
        errors: body?.errors ?? null,   // формат FormRequest, если применимо
        context: body?.context ?? null,
      });
    }

    return Promise.reject({
      kind: 'unknown',
      message: body?.message ?? 'Непредвиденная ошибка сервера.',
    });
  }
);
```

### 6.2 Единая точка показа Toast — хук мутаций

```js
// src/hooks/usePortalAction.js
export function usePortalAction() {
  const { showToast } = useToast();
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ portalId, action, forceEvacuate }) =>
      performAction(portalId, { action, force_evacuate: forceEvacuate }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['portals'] });
      queryClient.invalidateQueries({ queryKey: ['logs'] });
      showToast({ type: 'success', message: 'Действие выполнено.' });
    },
    onError: (error) => {
      showToast({
        type: 'error',
        message: error.message,
        // для business_rule ошибок — показываем машиночитаемый код мелким текстом,
        // это помогает при разборе edge cases во время демонстрации/тестирования
        detail: error.kind === 'business_rule' ? error.errorCode : null,
      });
    },
  });
}
```

### 6.3 Поведение UI при 422

1. **Toast** появляется всегда (и для `validation`, и для `business_rule`, и для `unknown`), автоскрытие через ~5 сек, с возможностью закрыть вручную.
2. **`ActionModal` не закрывается** при ошибке — пользователь видит форму с уже введёнными данными (например, галочкой `force_evacuate`) и может скорректировать действие (поставить галочку и повторить `close`).
3. Для `EVACUATION_REQUIRED` конкретно: получение этой ошибки при первой попытке `close` без флага триггерит **автоматическое раскрытие** `ForceEvacuateWarning` в модалке (даже если `creatures_count` на клиенте почему-то не был замечен) — защита от рассинхронизации кэша React Query.
4. Данные портала/списка **не оптимистично обновляются** (no optimistic update) — обновление происходит только после реального ответа сервера и `invalidateQueries`, что исключает мигание неверного состояния при откате 422.

---

## 7. Порядок реализации (рекомендуемая последовательность)

1. Backend: миграции + модели + `RiskCalculator` + Unit-тесты формулы риска.
2. Backend: `PortalActionService` + `PortalActionException` + Feature-тесты на BR-1/BR-2/BR-3 (включая happy path каждого действия).
3. Backend: контроллеры + ресурсы + `GET /api/portals` (с `summary`) + `GET /api/logs` + `seed-demo`.
4. Frontend: слой `api/` + React Query хуки, `PortalList`/`PortalCard`/`DashboardSummary` на реальных данных.
5. Frontend: `ActionModal` + `ToastProvider` + обработка 422 (ручное тестирование всех трёх BR через UI после `seed-demo`).
6. Frontend: `ActionLogsTable`, `AIWorklogPage`, финальная навигация.

---

## 8. Тестирование (PHPUnit, backend)

| Тест | Покрывает |
|---|---|
| `Unit/RiskCalculatorTest` | граничные значения формулы: `score` на границах 0/25/50/75/100, `time_to_collapse` ровно 15 (не должен давать +20) и 14 (должен) |
| `Feature/PortalActionTest` | по одному успешному кейсу на каждое действие (US-2..US-5) + по одному нарушению на каждое BR-1/BR-2/BR-3 → проверка HTTP 422, `error_code`, отсутствия записи в `portal_logs`, неизменности портала в БД |
| `Feature/PortalListTest` | фильтры `status`/`risk_level`, корректность `summary` (в т.ч. что `summary` не зависит от фильтра) |
| `Feature/PortalLogTest` | сортировка `-timestamp`, фильтр по `portal_id`, наличие `previous_state`/`new_state` |
| `Feature/DemoSeedTest` | 403 в окружении `production` (мокается через `app()->detectEnvironment`), 200 + корректный набор состояний в `local`/`testing` |

Frontend-тесты — вне явного требования задачи; ручная проверка через `seed-demo` + UI считается достаточной на данном этапе (можно вынести в отдельный план при необходимости Vitest/RTL).
