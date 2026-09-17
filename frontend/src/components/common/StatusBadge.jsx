const STYLES = {
  active: 'bg-blue-500/15 text-blue-300 border border-blue-500/40',
  stabilized: 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/40',
  closed: 'bg-slate-500/15 text-slate-300 border border-slate-500/40',
  under_review: 'bg-purple-500/15 text-purple-300 border border-purple-500/40',
};

const LABELS = {
  active: 'Активен',
  stabilized: 'Стабилизирован',
  closed: 'Закрыт',
  under_review: 'Под наблюдением',
};

export default function StatusBadge({ status }) {
  return (
    <span
      className={`inline-flex shrink-0 rounded-full px-2 py-1 text-xs font-medium ${STYLES[status] ?? 'bg-slate-500/15 text-slate-300 border border-slate-500/40'}`}
    >
      {LABELS[status] ?? status}
    </span>
  );
}
