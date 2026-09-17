import { useMutation, useQueryClient } from '@tanstack/react-query';
import { performAction } from '../api/portals';
import { useToast } from '../context/ToastContext';

export function usePortalAction() {
  const queryClient = useQueryClient();
  const { showToast } = useToast();

  return useMutation({
    mutationFn: ({ portalId, action, forceEvacuate }) =>
      performAction(portalId, { action, force_evacuate: forceEvacuate }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['portals'] });
      queryClient.invalidateQueries({ queryKey: ['logs'] });
      showToast({ type: 'success', message: 'Действие выполнено.' });
    },
    onError: (error) => {
      showToast({
        type: 'error',
        message: error.message,
        detail: error.kind === 'business_rule' ? `Код: ${error.errorCode}` : null,
      });
    },
  });
}
