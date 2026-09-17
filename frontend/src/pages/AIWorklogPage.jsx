import worklog from '../data/ai-worklog.json';

function formatDate(value) {
  try {
    return new Date(value).toLocaleString('ru-RU');
  } catch {
    return value;
  }
}

export default function AIWorklogPage() {
  return (
    <div className="mx-auto max-w-4xl space-y-6 px-4 py-6">
      <h2 className="text-lg font-semibold text-slate-200">AI Worklog</h2>
      <p className="text-sm text-slate-500">
        Структурированная история этапов разработки, промптов, ошибок AI и ручных правок.
      </p>

      <ol className="relative space-y-8 border-s border-slate-800 pl-6">
        {worklog.map((entry, index) => (
          <li key={index} className="relative">
            <span className="absolute -left-[29px] top-1 h-3 w-3 rounded-full bg-indigo-500 ring-4 ring-slate-950" />
            <div className="space-y-2 rounded-xl border border-slate-800 bg-slate-900/60 p-4">
              <div className="flex flex-wrap items-center justify-between gap-2">
                <h3 className="font-semibold text-slate-100">{entry.stage}</h3>
                <span className="text-xs text-slate-500">{formatDate(entry.timestamp)}</span>
              </div>

              <p className="text-sm text-slate-300">
                <span className="text-slate-500">Промпт: </span>
                {entry.prompt}
              </p>

              <div>
                <p className="mb-1 text-xs uppercase text-slate-500">Ошибки AI</p>
                {entry.ai_mistakes?.length ? (
                  <ul className="list-inside list-disc space-y-0.5 text-sm text-red-300">
                    {entry.ai_mistakes.map((mistake, i) => (
                      <li key={i}>{mistake}</li>
                    ))}
                  </ul>
                ) : (
                  <p className="text-sm text-slate-500">Ошибок не зафиксировано.</p>
                )}
              </div>

              <div>
                <p className="mb-1 text-xs uppercase text-slate-500">Ручные правки</p>
                {entry.manual_overrides?.length ? (
                  <ul className="list-inside list-disc space-y-0.5 text-sm text-amber-300">
                    {entry.manual_overrides.map((override, i) => (
                      <li key={i}>{override}</li>
                    ))}
                  </ul>
                ) : (
                  <p className="text-sm text-slate-500">Правок не было.</p>
                )}
              </div>
            </div>
          </li>
        ))}
      </ol>
    </div>
  );
}
