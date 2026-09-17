import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { fetchPortals } from '../api/portals';

export function usePortals(filters = {}) {
  return useQuery({
    queryKey: ['portals', filters],
    queryFn: () => fetchPortals(filters),
    placeholderData: keepPreviousData,
  });
}
