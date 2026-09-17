import { useState } from 'react';
import { usePortals } from '../hooks/usePortals';
import { useLogs } from '../hooks/useLogs';
import DashboardSummary from '../components/dashboard/DashboardSummary';
import PortalFilters from '../components/portals/PortalFilters';
import PortalList from '../components/portals/PortalList';
import ActionLogsTable from '../components/logs/ActionLogsTable';
import LoadingSpinner from '../components/common/LoadingSpinner';

export default function DashboardPage() {
  const [filters, setFilters] = useState({});
  const portalsQuery = usePortals(filters);
  const logsQuery = useLogs({ per_page: 20 });

  return (
    <div className="mx-auto max-w-6xl space-y-8 px-4 py-6">
      <section className="space-y-3">
        <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-200">Сводка</h2>
        {portalsQuery.isLoading ? (
          <LoadingSpinner />
        ) : (
          <DashboardSummary summary={portalsQuery.data?.summary} />
        )}
      </section>

      <section className="space-y-3">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-200">Порталы</h2>
          <PortalFilters filters={filters} onChange={setFilters} />
        </div>

        {portalsQuery.isLoading ? (
          <LoadingSpinner />
        ) : portalsQuery.isError ? (
          <p className="text-sm text-red-400">{portalsQuery.error.message}</p>
        ) : (
          <PortalList portals={portalsQuery.data.data} />
        )}
      </section>

      <section className="space-y-3">
        <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-200">Журнал событий</h2>
        {logsQuery.isLoading ? (
          <LoadingSpinner />
        ) : logsQuery.isError ? (
          <p className="text-sm text-red-400">{logsQuery.error.message}</p>
        ) : (
          <ActionLogsTable logs={logsQuery.data.data} />
        )}
      </section>
    </div>
  );
}
