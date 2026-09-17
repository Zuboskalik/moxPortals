export default function DashboardSummary({ summary }) {
  if (!summary) {
    return null;
  }

  const openCount = Object.entries(summary.by_status)
    .filter(([status]) => status !== 'closed')
    .reduce((sum, [, count]) => sum + count, 0);
  const criticalCount = summary.by_risk_level.CRITICAL ?? 0;
  const closedCount = summary.by_status.closed ?? 0;

  const tiles = [
    { label: 'Открытые порталы', value: openCount, accent: 'text-blue-400' },
    { label: 'Критический риск', value: criticalCount, accent: 'text-red-500' },
    { label: 'Закрытые порталы', value: closedCount, accent: 'text-slate-400' },
    { label: 'Существ в лаборатории', value: summary.creatures_total_open, accent: 'text-purple-400' },
    { label: 'Средний риск (активные)', value: summary.avg_risk_score_active, accent: 'text-amber-400' },
  ];

  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
      {tiles.map((tile) => (
        <div key={tile.label} className="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
          <p className="mb-1 text-xs text-slate-500">{tile.label}</p>
          <p className={`text-2xl font-semibold ${tile.accent}`}>{tile.value}</p>
        </div>
      ))}
    </div>
  );
}
