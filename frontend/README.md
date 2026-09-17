# Frontend — Лаборатория нестабильных порталов

SPA на React + Vite для смотрителя портала. Общее описание проекта — в [корневом README](../README.md).

## Стек

- React 19, Vite
- Tailwind CSS v4 (через `@tailwindcss/vite`, без отдельного `postcss.config.js`)
- Axios + TanStack React Query
- React Router

## Установка и запуск

```bash
npm install
cp .env.example .env
npm run dev
```

Приложение поднимется на `http://localhost:5173` и обращается к API по адресу из `VITE_API_URL` (по умолчанию `http://localhost:8000/api` — backend должен быть запущен, см. [../backend/README.md](../backend/README.md)).

## Сборка

```bash
npm run build
npm run preview
```

## Структура

```
src/
  api/          axios-клиент + функции запросов (portals, logs)
  hooks/        React Query хуки (usePortals, useLogs, usePortalAction, useSeedDemo)
  context/      ToastContext — глобальные уведомления
  components/
    layout/     Header (навигация + кнопка сброса демо-данных)
    portals/    PortalCard, PortalTable, PortalList, PortalFilters
    actions/    ActionModal (диалоги действий, предупреждение об эвакуации)
    dashboard/  DashboardSummary
    logs/       ActionLogsTable (журнал с diff previous/new state)
    common/     RiskBadge, StatusBadge, ConfirmDialog, LoadingSpinner
  pages/        DashboardPage, AIWorklogPage
  data/         ai-worklog.json — статические данные вкладки AI Worklog
```

## Обработка ошибок 422

`src/api/client.js` перехватывает ответы `422` и нормализует их в единый формат (`business_rule` — доменная ошибка с `error_code`, `validation` — стандартная ошибка Laravel-валидации). `ActionModal` не закрывается при ошибке, `ToastProvider` показывает уведомление с текстом ошибки и кодом (для доменных ошибок).
