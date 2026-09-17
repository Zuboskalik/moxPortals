const STYLES = {
  CRITICAL: 'bg-red-600 text-white',
  HIGH: 'bg-orange-500 text-white',
  MEDIUM: 'bg-yellow-400 text-slate-900',
  LOW: 'bg-emerald-500 text-white',
};

const LABELS = {
  CRITICAL: 'CRITICAL',
  HIGH: 'HIGH',
  MEDIUM: 'MEDIUM',
  LOW: 'LOW',
};

export default function RiskBadge({ level, score }) {
  return (
    <span
      className={`inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold ${STYLES[level] ?? 'bg-slate-500 text-white'}`}
    >
      {LABELS[level] ?? level}
      {typeof score === 'number' && <span className="opacity-80">· {score}</span>}
    </span>
  );
}
