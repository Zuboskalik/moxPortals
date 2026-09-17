import { useMutation, useQueryClient } from '@tanstack/react-query';
import { seedDemo } from '../api/portals';
import { useToast } from '../context/ToastContext';

export function useSeedDemo() {
  const queryClient = useQueryClient();
  const { showToast } = useToast();

  return useMutation({
    mutationFn: seedDemo,
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['portals'] });
      queryClient.invalidateQueries({ queryKey: ['logs'] });
      showToast({ type: 'success', message: data?.message ?? 'Демо-данные пересозданы.' });
    },
    onError: (error) => {
      showToast({ type: 'error', message: error.message });
    },
  });
}
