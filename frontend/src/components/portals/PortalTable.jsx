import RiskBadge from '../common/RiskBadge';
import StatusBadge from '../common/StatusBadge';

const ACTIONS = [
  { key: 'stabilize', label: 'Стабилизировать' },
  { key: 'dispatch_observer', label: 'Наблюдатель' },
  { key: 'mark_under_review', label: 'Под наблюдение' },
  { key: 'close', label: 'Закрыть' },
];

export default function PortalTable({ portals, onAction }) {
  return (
    <div className="overflow-x-auto rounded-xl border border-slate-800">
      <table className="min-w-full text-left text-sm text-slate-300">
        <thead className="bg-slate-900 text-xs uppercase text-slate-400">
          <tr>
            <th className="px-3 py-2">Название</th>
            <th className="px-3 py-2">Мир</th>
            <th className="px-3 py-2">Энергия</th>
            <th className="px-3 py-2">Стабильность</th>
            <th className="px-3 py-2">До коллапса</th>
            <th className="px-3 py-2">Существ</th>
            <th className="px-3 py-2">Статус</th>
            <th className="px-3 py-2">Риск</th>
            <th className="px-3 py-2">Действия</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-800">
          {portals.map((portal) => {
            const isClosed = portal.status === 'closed';
            return (
              <tr key={portal.id} className={portal.risk_level === 'CRITICAL' ? 'bg-red-950/30' : ''}>
                <td className="px-3 py-2 font-medium text-slate-100">{portal.name}</td>
                <td className="px-3 py-2">{portal.destination_world}</td>
                <td className="px-3 py-2">{portal.energy_level}</td>
                <td className="px-3 py-2">{portal.stability.toFixed(2)}</td>
                <td className="px-3 py-2">{portal.time_to_collapse} мин</td>
                <td className="px-3 py-2">{portal.creatures_count}</td>
                <td className="px-3 py-2">
                  <StatusBadge status={portal.status} />
                </td>
                <td className="px-3 py-2">
                  <RiskBadge level={portal.risk_level} score={portal.risk_score} />
                </td>
                <td className="px-3 py-2">
                  <div className="flex flex-wrap gap-1">
                    {ACTIONS.map((item) => (
                      <button
                        key={item.key}
                        type="button"
                        disabled={isClosed}
                        onClick={() => onAction(portal, item.key)}
                        className="rounded bg-slate-800 px-2 py-1 text-xs transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-30"
                      >
                        {item.label}
                      </button>
                    ))}
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}
