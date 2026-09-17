import { useState } from 'react';
import { NavLink } from 'react-router-dom';
import { useSeedDemo } from '../../hooks/useSeedDemo';
import ConfirmDialog from '../common/ConfirmDialog';

function tabClass({ isActive }) {
  return `rounded-lg px-3 py-1.5 text-sm font-medium transition ${
    isActive ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800'
  }`;
}

export default function Header() {
  const [confirmOpen, setConfirmOpen] = useState(false);
  const seedDemo = useSeedDemo();

  function handleConfirm() {
    seedDemo.mutate(undefined, { onSuccess: () => setConfirmOpen(false) });
  }

  return (
    <header className="sticky top-0 z-40 border-b border-slate-800 bg-slate-900/80 backdrop-blur">
      <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <h1 className="text-base font-semibold text-slate-100 sm:text-lg">
          🌀 Лаборатория нестабильных порталов
        </h1>

        <nav className="flex items-center gap-2">
          <NavLink to="/" end className={tabClass}>
            Панель управления
          </NavLink>
          <NavLink to="/ai-worklog" className={tabClass}>
            AI Worklog
          </NavLink>
        </nav>

        <button
          type="button"
          onClick={() => setConfirmOpen(true)}
          className="rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-amber-500"
        >
          Сбросить к демо-состояниям
        </button>
      </div>

      <ConfirmDialog
        open={confirmOpen}
        title="Сбросить к демо-состояниям?"
        message={
          'Все текущие порталы и журнал событий будут удалены и заменены\nнаправленным набором демо-порталов для проверки всех сценариев.'
        }
        confirmLabel="Сбросить"
        danger
        isLoading={seedDemo.isPending}
        onConfirm={handleConfirm}
        onCancel={() => setConfirmOpen(false)}
      />
    </header>
  );
}
