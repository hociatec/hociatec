type MaintenanceModeFormProps = {
  enabled: boolean;
  setEnabled: (value: boolean) => void;
  message: string;
  setMessage: (value: string) => void;
  busy: boolean;
  onSubmit: (event: React.FormEvent<HTMLFormElement>) => void;
};

export const MaintenanceModeForm = ({
  enabled,
  setEnabled,
  message,
  setMessage,
  busy,
  onSubmit,
}: MaintenanceModeFormProps) => (
  <form onSubmit={onSubmit} className="rounded-xl border border-white/10 bg-white/[0.04] p-6">
    <h2 className="text-xl font-semibold text-white">Mode maintenance</h2>
    <p className="mt-2 text-sm text-stone-500">
      Le site public affiche un écran de maintenance et les APIs publiques renvoient un `503`.
      L’admin et la connexion restent accessibles.
    </p>

    <label className="mt-6 flex items-center gap-3 rounded-2xl border border-white/10 bg-brand-900/70 p-4 text-stone-100">
      <input
        type="checkbox"
        checked={enabled}
        onChange={(event) => setEnabled(event.target.checked)}
        className="h-5 w-5 rounded border-brand-600 text-amber-500"
      />
      Activer le mode maintenance
    </label>

    <label className="mt-5 flex flex-col gap-2 text-sm font-medium text-stone-200">
      Message public
      <textarea
        value={message}
        onChange={(event) => setMessage(event.target.value)}
        rows={4}
        className="rounded-xl border border-brand-700 bg-brand-900 px-4 py-3 text-white"
      />
    </label>

    <button type="submit" disabled={busy} className="btn-primary mt-6">
      Appliquer le mode maintenance
    </button>
  </form>
);
