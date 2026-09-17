const STATUS_OPTIONS = [
  { value: '', label: 'Все статусы' },
  { value: 'active', label: 'Активен' },
  { value: 'stabilized', label: 'Стабилизирован' },
  { value: 'under_review', label: 'Под наблюдением' },
  { value: 'closed', label: 'Закрыт' },
];

const RISK_OPTIONS = [
  { value: '', label: 'Любой риск' },
  { value: 'CRITICAL', label: 'CRITICAL' },
  { value: 'HIGH', label: 'HIGH' },
  { value: 'MEDIUM', label: 'MEDIUM' },
  { value: 'LOW', label: 'LOW' },
];

export default function PortalFilters({ filters, onChange }) {
  return (
    <div className="flex flex-wrap gap-3">
      <select
        value={filters.status ?? ''}
        onChange={(event) => onChange({ ...filters, status: event.target.value || undefined })}
        className="rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm text-slate-200"
      >
        {STATUS_OPTIONS.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>

      <select
        value={filters.risk_level ?? ''}
        onChange={(event) => onChange({ ...filters, risk_level: event.target.value || undefined })}
        className="rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-sm text-slate-200"
      >
        {RISK_OPTIONS.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </div>
  );
}
