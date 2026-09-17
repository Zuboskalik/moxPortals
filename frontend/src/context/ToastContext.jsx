import { createContext, useCallback, useContext, useRef, useState } from 'react';

const ToastContext = createContext(null);

let idCounter = 0;

const TOAST_STYLES = {
  success: 'bg-emerald-600 border-emerald-500',
  error: 'bg-red-600 border-red-500',
  info: 'bg-slate-700 border-slate-600',
};

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);
  const timers = useRef({});

  const removeToast = useCallback((id) => {
    setToasts((prev) => prev.filter((toast) => toast.id !== id));
    clearTimeout(timers.current[id]);
    delete timers.current[id];
  }, []);

  const showToast = useCallback(
    ({ type = 'info', message, detail = null }) => {
      const id = ++idCounter;
      setToasts((prev) => [...prev, { id, type, message, detail }]);
      timers.current[id] = setTimeout(() => removeToast(id), 5000);
    },
    [removeToast],
  );

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      <div className="fixed top-4 right-4 z-[100] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2">
        {toasts.map((toast) => (
          <div
            key={toast.id}
            role="alert"
            className={`flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm text-white shadow-lg ${TOAST_STYLES[toast.type] ?? TOAST_STYLES.info}`}
          >
            <div>
              <p className="font-medium">{toast.message}</p>
              {toast.detail && <p className="mt-1 text-xs opacity-80">{toast.detail}</p>}
            </div>
            <button
              type="button"
              onClick={() => removeToast(toast.id)}
              className="text-white/70 transition hover:text-white"
              aria-label="Закрыть уведомление"
            >
              ×
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

export function useToast() {
  const ctx = useContext(ToastContext);
  if (!ctx) {
    throw new Error('useToast должен использоваться внутри ToastProvider');
  }
  return ctx;
}
