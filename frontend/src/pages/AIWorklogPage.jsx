import worklog from '../data/ai-worklog.json';

function formatDate(value) {
  try {
    return new Date(value).toLocaleString('ru-RU');
  } catch {
    return value;
  }
}

function SectionTitle({ children }) {
  return <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-200">{children}</h2>;
}

function MetaOverview({ meta }) {
  return (
    <section className="space-y-3">
      <SectionTitle>Инструменты и метрики сессии</SectionTitle>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
          <p className="mb-2 text-xs uppercase text-slate-500">Используемые инструменты</p>
          <ul className="list-inside list-disc space-y-1 text-sm text-slate-300">
            {meta.tools.map((tool, i) => (
              <li key={i}>{tool}</li>
            ))}
          </ul>
        </div>

        <div className="space-y-4">
          <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <p className="mb-1 text-xs uppercase text-slate-500">Общее время разработки</p>
            <p className="text-lg font-semibold text-slate-100">{meta.total_duration_human}</p>
            <p className="mt-1 text-xs text-slate-500">
              {formatDate(meta.session_started_at)} — {formatDate(meta.session_ended_at)}
            </p>
          </div>

          <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <p className="mb-1 text-xs uppercase text-slate-500">Данные по токенам</p>
            {meta.token_usage.available ? (
              <p className="text-lg font-semibold text-slate-100">{meta.token_usage.value}</p>
            ) : (
              <>
                <p className="text-sm font-medium text-amber-300">{meta.token_usage.rough_estimate}</p>
                <p className="mt-1 text-xs text-slate-500">{meta.token_usage.note}</p>
              </>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}

function StageTable({ stages }) {
  return (
    <section className="space-y-3">
      <SectionTitle>Этапы: Specify → Plan → Tasks → Implement</SectionTitle>
      <div className="overflow-x-auto rounded-xl border border-slate-800">
        <table className="min-w-full text-left text-sm text-slate-300">
          <thead className="bg-slate-900 text-xs uppercase text-slate-400">
            <tr>
              <th className="px-3 py-2 align-top">Этап</th>
              <th className="px-3 py-2 align-top">Что делал человек</th>
              <th className="px-3 py-2 align-top">Что делал AI</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-800">
            {stages.map((row) => (
              <tr key={row.stage}>
                <td className="px-3 py-3 align-top font-medium text-slate-100">{row.stage}</td>
                <td className="px-3 py-3 align-top text-slate-300">{row.human_did}</td>
                <td className="px-3 py-3 align-top text-slate-400">{row.ai_did}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  );
}

function KeyPrompts({ prompts }) {
  return (
    <section className="space-y-3">
      <SectionTitle>Ключевые промпты</SectionTitle>
      <ol className="space-y-2">
        {prompts.map((prompt, i) => (
          <li key={i} className="rounded-xl border border-slate-800 bg-slate-900/60 p-3 text-sm text-slate-300">
            <span className="mr-2 font-mono text-xs text-indigo-400">#{i + 1}</span>
            {prompt}
          </li>
        ))}
      </ol>
    </section>
  );
}

function HumanDecisions({ decisions }) {
  return (
    <section className="space-y-3">
      <SectionTitle>Принятые человеком решения</SectionTitle>
      <ul className="space-y-2">
        {decisions.map((decision, i) => (
          <li
            key={i}
            className="rounded-xl border border-blue-900/50 bg-blue-950/30 p-3 text-sm text-blue-100"
          >
            {decision}
          </li>
        ))}
      </ul>
    </section>
  );
}

function AiMistakes({ mistakes }) {
  return (
    <section className="space-y-3">
      <SectionTitle>Ошибки AI и их исправление</SectionTitle>
      {mistakes.length === 0 ? (
        <p className="text-sm text-slate-500">Ошибок не зафиксировано.</p>
      ) : (
        <div className="space-y-3">
          {mistakes.map((entry, i) => (
            <div key={i} className="rounded-xl border border-red-900/50 bg-red-950/20 p-4 text-sm">
              <p className="mb-1 text-xs uppercase text-slate-500">{entry.context}</p>
              <p className="mb-2 text-red-300">
                <span className="font-semibold">Ошибка: </span>
                {entry.mistake}
              </p>
              <p className="text-emerald-300">
                <span className="font-semibold">Исправление: </span>
                {entry.fix}
              </p>
            </div>
          ))}
        </div>
      )}
    </section>
  );
}

function ManualChecklist({ items }) {
  return (
    <section className="space-y-3">
      <SectionTitle>Чек-лист ручной проверки</SectionTitle>
      <ul className="space-y-1.5">
        {items.map((entry, i) => (
          <li
            key={i}
            className="flex items-start gap-2 rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2 text-sm text-slate-300"
          >
            <span className={entry.status === 'done' ? 'text-emerald-400' : 'text-slate-500'}>
              {entry.status === 'done' ? '✓' : '○'}
            </span>
            {entry.item}
          </li>
        ))}
      </ul>
    </section>
  );
}

export default function AIWorklogPage() {
  return (
    <div className="mx-auto max-w-4xl space-y-10 px-4 py-6">
      <div>
        <h1 className="text-lg font-semibold text-slate-100">AI Worklog</h1>
        <p className="mt-1 text-sm text-slate-500">
          Структурированные данные о ходе разработки: инструменты, время, промпты, решения, ошибки AI и их
          исправления, чек-лист ручной проверки.
        </p>
      </div>

      <MetaOverview meta={worklog.meta} />
      <StageTable stages={worklog.stage_table} />
      <KeyPrompts prompts={worklog.key_prompts} />
      <HumanDecisions decisions={worklog.human_decisions} />
      <AiMistakes mistakes={worklog.ai_mistakes} />
      <ManualChecklist items={worklog.manual_verification_checklist} />
    </div>
  );
}
