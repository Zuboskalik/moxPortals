import { useState } from 'react';
import PortalCard from './PortalCard';
import PortalTable from './PortalTable';
import ActionModal from '../actions/ActionModal';

export default function PortalList({ portals }) {
  const [view, setView] = useState('cards');
  const [pendingAction, setPendingAction] = useState(null);

  function handleAction(portal, action) {
    setPendingAction({ portal, action });
  }

  return (
    <div className="space-y-3">
      <div className="flex justify-end gap-1">
        <button
          type="button"
          onClick={() => setView('cards')}
          className={`rounded-lg px-3 py-1 text-xs font-medium transition ${
            view === 'cards' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
          }`}
        >
          Карточки
        </button>
        <button
          type="button"
          onClick={() => setView('table')}
          className={`rounded-lg px-3 py-1 text-xs font-medium transition ${
            view === 'table' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
          }`}
        >
          Таблица
        </button>
      </div>

      {portals.length === 0 ? (
        <p className="py-10 text-center text-sm text-slate-500">Порталов не найдено.</p>
      ) : view === 'cards' ? (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {portals.map((portal) => (
            <PortalCard key={portal.id} portal={portal} onAction={handleAction} />
          ))}
        </div>
      ) : (
        <PortalTable portals={portals} onAction={handleAction} />
      )}

      {pendingAction && (
        <ActionModal
          key={`${pendingAction.portal.id}-${pendingAction.action}`}
          portal={pendingAction.portal}
          action={pendingAction.action}
          onClose={() => setPendingAction(null)}
        />
      )}
    </div>
  );
}
