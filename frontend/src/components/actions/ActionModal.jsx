import { useState } from 'react';
import { usePortalAction } from '../../hooks/usePortalAction';
import RiskBadge from '../common/RiskBadge';

const ACTION_LABELS = {
  stabilize: 'Стабилизировать портал',
  close: 'Закрыть портал',
  dispatch_observer: 'Отправить наблюдателя',
  mark_under_review: 'Перевести под наблюдение',
};

function pluralizeCreature(count) {
  const mod10 = count % 10;
  const mod100 = count % 100;
  if (mod10 === 1 && mod100 !== 11) return 'существо';
  if ([2, 3, 4].includes(mod10) && ![12, 13, 14].includes(mod100)) return 'существа';
  return 'существ';
}

export default function ActionModal({ portal, action, onClose }) {
  const [forceEvacuate, setForceEvacuate] = useState(false);
  const mutation = usePortalAction();

  const isCriticalObserverBlocked = action === 'dispatch_observer' && portal.risk_level === 'CRITICAL';
  const needsEvacuationConfirm = action === 'close' && portal.creatures_count > 0;
  // если сервер вернул EVACUATION_REQUIRED, а локально мы этого не ожидали (рассинхронизация кэша) —
  // всё равно раскрываем предупреждение, чтобы пользователь мог подтвердить эвакуацию повторно
  const evacuationRequiredByServer = mutation.error?.errorCode === 'EVACUATION_REQUIRED';
  const showEvacuationWarning = needsEvacuationConfirm || evacuationRequiredByServer;

  const confirmDisabled =
    mutation.isPending || isCriticalObserverBlocked || (showEvacuationWarning && !forceEvacuate);

  function handleConfirm() {
    mutation.mutate(
      {
        portalId: portal.id,
        action,
        forceEvacuate: showEvacuationWarning ? forceEvacuate : false,
      },
      { onSuccess: onClose },
    );
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4">
      <div className="w-full max-w-md rounded-xl border border-slate-700 bg-slate-900 p-5 shadow-xl">
        <div className="mb-3 flex items-start justify-between gap-3">
          <h3 className="text-lg font-semibold text-slate-100">{ACTION_LABELS[action] ?? action}</h3>
          <RiskBadge level={portal.risk_level} score={portal.risk_score} />
        </div>

        <p className="mb-4 text-sm text-slate-300">
          Портал <span className="font-medium text-slate-100">«{portal.name}»</span> ({portal.destination_world})
        </p>

        {isCriticalObserverBlocked && (
          <div className="mb-4 rounded-lg border border-red-700 bg-red-950 p-3 text-sm text-red-200">
            ⚠ Риск CRITICAL. Отправка наблюдателя запрещена системой — портал слишком опасен.
          </div>
        )}

        {showEvacuationWarning && !isCriticalObserverBlocked && (
          <div className="mb-4 space-y-2 rounded-lg border border-amber-700 bg-amber-950 p-3 text-sm text-amber-200">
            <p>
              ⚠ Внимание: в портале {portal.creatures_count} {pluralizeCreature(portal.creatures_count)}!
              Эвакуировать перед закрытием?
            </p>
            <label className="flex items-center gap-2 text-amber-100">
              <input
                type="checkbox"
                checked={forceEvacuate}
                onChange={(event) => setForceEvacuate(event.target.checked)}
              />
              Подтверждаю принудительную эвакуацию
            </label>
          </div>
        )}

        {mutation.isError && !evacuationRequiredByServer && (
          <div className="mb-4 rounded-lg border border-red-700 bg-red-950 p-3 text-sm text-red-200">
            {mutation.error.message}
            {mutation.error.errorCode && (
              <div className="mt-1 text-xs opacity-70">Код: {mutation.error.errorCode}</div>
            )}
          </div>
        )}

        <div className="mt-2 flex justify-end gap-2">
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg px-3 py-1.5 text-sm text-slate-300 transition hover:bg-slate-800"
          >
            Отмена
          </button>
          <button
            type="button"
            onClick={handleConfirm}
            disabled={confirmDisabled}
            className="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-40"
          >
            {mutation.isPending ? 'Выполняется…' : 'Подтвердить'}
          </button>
        </div>
      </div>
    </div>
  );
}
