import RiskBadge from '../common/RiskBadge';
import StatusBadge from '../common/StatusBadge';

const ACTIONS = [
  { key: 'stabilize', label: 'Стабилизировать' },
  { key: 'dispatch_observer', label: 'Наблюдатель' },
  { key: 'mark_under_review', label: 'Под наблюдение' },
  { key: 'close', label: 'Закрыть' },
];

const RISK_BORDER = {
  CRITICAL: 'border-red-600/70 ring-1 ring-red-600/40',
  HIGH: 'border-orange-500/60',
  MEDIUM: 'border-yellow-500/40',
  LOW: 'border-emerald-500/30',
};

export default function PortalCard({ portal, onAction }) {
  const isClosed = portal.status === 'closed';

  return (
    <div
      className={`flex flex-col gap-3 rounded-xl border bg-slate-900/60 p-4 ${RISK_BORDER[portal.risk_level] ?? 'border-slate-700'}`}
    >
      <div className="flex items-start justify-between gap-2">
        <div>
          <h3 className="font-semibold text-slate-100">{portal.name}</h3>
          <p className="text-xs text-slate-400">{portal.destination_world}</p>
        </div>
        <RiskBadge level={portal.risk_level} score={portal.risk_score} />
      </div>

      <dl className="grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-slate-300">
        <div>
          <dt className="text-slate-500">Энергия</dt>
          <dd>{portal.energy_level}</dd>
        </div>
        <div>
          <dt className="text-slate-500">Стабильность</dt>
          <dd>{portal.stability.toFixed(2)}</dd>
        </div>
        <div>
          <dt className="text-slate-500">До коллапса</dt>
          <dd>{portal.time_to_collapse} мин</dd>
        </div>
        <div>
          <dt className="text-slate-500">Существ</dt>
          <dd>{portal.creatures_count}</dd>
        </div>
      </dl>

      <div>
        <StatusBadge status={portal.status} />
      </div>

      <div className="flex flex-wrap gap-2 border-t border-slate-800 pt-3">
        {ACTIONS.map((item) => (
          <button
            key={item.key}
            type="button"
            disabled={isClosed}
            onClick={() => onAction(portal, item.key)}
            className="rounded-md bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-200 transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-30"
          >
            {item.label}
          </button>
        ))}
      </div>
    </div>
  );
}
