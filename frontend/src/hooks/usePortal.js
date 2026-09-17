import { useQuery } from '@tanstack/react-query';
import { fetchPortal } from '../api/portals';

export function usePortal(id) {
  return useQuery({
    queryKey: ['portal', id],
    queryFn: () => fetchPortal(id),
    enabled: Boolean(id),
  });
}
