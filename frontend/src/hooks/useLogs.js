import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { fetchLogs } from '../api/logs';

export function useLogs(filters = {}) {
  return useQuery({
    queryKey: ['logs', filters],
    queryFn: () => fetchLogs(filters),
    placeholderData: keepPreviousData,
  });
}
