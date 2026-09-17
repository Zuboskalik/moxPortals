# Backend — Лаборатория нестабильных порталов

REST API на Laravel 11 + MySQL. Общее описание проекта — в [корневом README](../README.md), детали дизайна — в [../specify.md](../specify.md) и [../plan.md](../plan.md).

## Стек

- PHP 8.2, Laravel 11
- MySQL 8
- PHPUnit 11 (тесты гоняются на изолированной in-memory SQLite)
- Laravel Pint (стиль кода)

## Установка и запуск

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Убедитесь, что в `.env` настроено подключение к локальной MySQL (по умолчанию уже прописано):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=moxPortals
DB_USERNAME=mysql
DB_PASSWORD=mysql
```

База `moxPortals` должна существовать в MySQL заранее (создайте её вручную, если ещё не создана).

```bash
php artisan migrate
php artisan serve
```

API поднимется на `http://127.0.0.1:8000/api`.

Наполнить БД демо-данными для ручной проверки (доступно только в `local`/`testing`, сбрасывает текущие данные):

```bash
curl -X POST http://127.0.0.1:8000/api/portals/seed-demo -H "Accept: application/json"
```

## Тесты

```bash
php artisan test
```

Ожидаемый результат: **39 тестов, все зелёные** (Unit — формула риска; Feature — действия над порталами, бизнес-правила, список порталов, журнал событий, demo-seed).

Проверка стиля кода:

```bash
vendor/bin/pint
```

## API

| Метод | Путь | Описание |
|---|---|---|
| GET | `/api/portals` | Список порталов + фильтры (`status`, `risk_level`) + сортировка (`sort`) + `summary` |
| GET | `/api/portals/{id}` | Детали одного портала |
| POST | `/api/portals/{id}/action` | Выполнить действие (`action`, `force_evacuate`) |
| GET | `/api/logs` | Журнал событий + фильтры (`portal_id`, `action_type`) |
| POST | `/api/portals/seed-demo` | Сброс БД к демо-состояниям (только `local`/`testing`) |

## Формула риска

```
Score = energy_level * 0.4 + (1 - stability) * 40 + (time_to_collapse < 15 ? 20 : 0)
```

`Score` округляется и ограничивается диапазоном `0..100`. Уровень риска: `>=75` CRITICAL, `50–74` HIGH, `25–49` MEDIUM, `<25` LOW. Реализация — [app/Services/RiskCalculator.php](app/Services/RiskCalculator.php).

## Бизнес-правила (HTTP 422)

Реализованы в [app/Services/PortalActionService.php](app/Services/PortalActionService.php) и [app/Exceptions/PortalActionException.php](app/Exceptions/PortalActionException.php):

| Код ошибки | Правило |
|---|---|
| `PORTAL_ALREADY_CLOSED` | Нельзя `stabilize`/`mark_under_review` портал со статусом `closed` |
| `RISK_TOO_HIGH_FOR_OBSERVER` | Нельзя `dispatch_observer`, если `risk_level == CRITICAL` |
| `EVACUATION_REQUIRED` | Нельзя `close` портал с `creatures_count > 0` без `force_evacuate=true` |

Каждое нарушение возвращает `422` с телом `{ message, error_code, context }` и не создаёт запись в журнале — операция атомарна (обёрнута в транзакцию).
