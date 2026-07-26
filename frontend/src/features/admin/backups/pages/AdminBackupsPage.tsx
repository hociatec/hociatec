
import { useAdminBackups } from '../hooks/useAdminBackups';
import { LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { AdminBackupsOverview } from '@/features/admin/backups/components/AdminBackupsOverview';

const formatDate = (value?: string | null) => {
  if (!value) return 'Jamais';

  return new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(value));
};

const formatBytes = (bytes?: number | null) => {
  if (!bytes) return '0 o';
  const units = ['o', 'Ko', 'Mo', 'Go'];
  let value = bytes;
  let index = 0;
  while (value >= 1024 && index < units.length - 1) {
    value /= 1024;
    index += 1;
  }

  return `${value.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
};

export const AdminBackupsPage = () => {
  useDocumentTitle('Admin - Sauvegardes');
  const {
    status,
    intervalHours,
    setIntervalHours,
    retentionCount,
    setRetentionCount,
    enabled,
    setEnabled,
    maintenanceEnabled,
    setMaintenanceEnabled,
    maintenanceMessage,
    setMaintenanceMessage,
    loading,
    busy,
    message,
    error,
    submitSettings,
    launchBackup,
    submitMaintenance,
  } = useAdminBackups();

  if (loading) {
    return (
      <LoadingState className="mx-auto my-12 w-full max-w-6xl">
        Chargement des sauvegardes...
      </LoadingState>
    );
  }

  return (
    <section className="mx-auto flex w-full max-w-6xl flex-col gap-8 px-6 py-12">
      <header className="rounded-xl border border-amber-200/20 bg-brand-900/80 p-8 shadow-2xl shadow-black/20">
        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-amber-300">
          Exploitation
        </p>
        <h1 className="mt-3 text-4xl font-bold text-white">Sauvegardes et maintenance</h1>
        <p className="mt-4 max-w-3xl text-stone-500">
          Pilotez les sauvegardes MySQL, la fréquence d’exécution, la rétention locale et le mode
          maintenance public.
        </p>
      </header>

      <div aria-live="polite" className="space-y-3">
        {message ? (
          <div className="rounded-2xl border border-emerald-300/40 bg-emerald-950/60 p-4 text-emerald-100">
            {message}
          </div>
        ) : null}
        {error ? (
          <div
            className="rounded-2xl border border-red-300/40 bg-red-950/70 p-4 text-red-100"
            role="alert"
          >
            {error}
          </div>
        ) : null}
      </div>

      {status && <AdminBackupsOverview status={status} />}

      {status && (!status.tools.mysqldumpAvailable || !status.tools.gzipAvailable) ? (
        <div
          className="rounded-2xl border border-red-300/40 bg-red-950/60 p-5 text-red-100"
          role="alert"
        >
          Outils serveur incomplets: `mysqldump` ou l’extension PHP `zlib` est indisponible. Les
          sauvegardes ne pourront pas être fiables tant que le serveur n’est pas corrigé.
        </div>
      ) : null}

      <div className="grid gap-6 lg:grid-cols-[1fr_0.9fr]">
        <form
          onSubmit={submitSettings}
          className="rounded-xl border border-white/10 bg-white/[0.04] p-6"
        >
          <h2 className="text-xl font-semibold text-white">Configuration automatique</h2>
          <p className="mt-2 text-sm text-stone-500">
            La configuration est appliquée par la commande cron indiquée plus bas. Le bouton manuel
            utilise la même procédure, avec verrou anti-concurrence et rétention automatique.
          </p>

          <div className="mt-6 grid gap-5 sm:grid-cols-2">
            <label className="flex items-center gap-3 rounded-2xl border border-white/10 bg-brand-900/70 p-4 text-stone-100">
              <input
                type="checkbox"
                checked={enabled}
                onChange={(event) => setEnabled(event.target.checked)}
                className="h-5 w-5 rounded border-brand-600 text-amber-500"
              />
              Activer les sauvegardes planifiées
            </label>

            <label className="flex flex-col gap-2 text-sm font-medium text-stone-200">
              Fréquence
              <select
                value={intervalHours}
                onChange={(event) => setIntervalHours(Number(event.target.value))}
                className="rounded-xl border border-brand-700 bg-brand-900 px-4 py-3 text-white"
              >
                <option value={6}>Toutes les 6 heures</option>
                <option value={12}>Toutes les 12 heures</option>
                <option value={24}>Tous les jours</option>
                <option value={48}>Tous les 2 jours</option>
                <option value={168}>Toutes les semaines</option>
              </select>
            </label>

            <label className="flex flex-col gap-2 text-sm font-medium text-stone-200">
              Nombre de sauvegardes à garder
              <input
                type="number"
                min={1}
                max={90}
                value={retentionCount}
                onChange={(event) => setRetentionCount(Number(event.target.value))}
                className="rounded-xl border border-brand-700 bg-brand-900 px-4 py-3 text-white"
              />
            </label>

            <div className="rounded-2xl border border-white/10 bg-brand-900/70 p-4 text-sm text-stone-500">
              Prochaine exécution prévue:
              <strong className="mt-1 block text-white">
                {formatDate(status?.settings.nextRunAt)}
              </strong>
            </div>
          </div>

          <div className="mt-6 flex flex-wrap gap-3">
            <button type="submit" disabled={busy} className="btn-primary">
              Enregistrer la configuration
            </button>
            <button type="button" disabled={busy} onClick={launchBackup} className="btn-secondary">
              Sauvegarder maintenant
            </button>
          </div>
        </form>

        <form
          onSubmit={submitMaintenance}
          className="rounded-xl border border-white/10 bg-white/[0.04] p-6"
        >
          <h2 className="text-xl font-semibold text-white">Mode maintenance</h2>
          <p className="mt-2 text-sm text-stone-500">
            Le site public affiche un écran de maintenance et les APIs publiques renvoient un `503`.
            L’admin et la connexion restent accessibles.
          </p>

          <label className="mt-6 flex items-center gap-3 rounded-2xl border border-white/10 bg-brand-900/70 p-4 text-stone-100">
            <input
              type="checkbox"
              checked={maintenanceEnabled}
              onChange={(event) => setMaintenanceEnabled(event.target.checked)}
              className="h-5 w-5 rounded border-brand-600 text-amber-500"
            />
            Activer le mode maintenance
          </label>

          <label className="mt-5 flex flex-col gap-2 text-sm font-medium text-stone-200">
            Message public
            <textarea
              value={maintenanceMessage}
              onChange={(event) => setMaintenanceMessage(event.target.value)}
              rows={4}
              className="rounded-xl border border-brand-700 bg-brand-900 px-4 py-3 text-white"
            />
          </label>

          <button type="submit" disabled={busy} className="btn-primary mt-6">
            Appliquer le mode maintenance
          </button>
        </form>
      </div>

      <section className="rounded-xl border border-white/10 bg-white/[0.04] p-6">
        <h2 className="text-xl font-semibold text-white">Sauvegardes effectuées</h2>
        <div className="mt-5 overflow-x-auto">
          <table className="min-w-full divide-y divide-white/10 text-left text-sm">
            <thead className="text-xs uppercase tracking-[0.18em] text-stone-400">
              <tr>
                <th className="py-3 pr-4">Fichier</th>
                <th className="py-3 pr-4">Date</th>
                <th className="py-3 pr-4">Taille</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/10 text-stone-200">
              {status?.backups.length ? (
                status.backups.map((backup) => (
                  <tr key={backup.filename}>
                    <td className="py-4 pr-4 font-medium text-white">{backup.filename}</td>
                    <td className="py-4 pr-4">{formatDate(backup.createdAt)}</td>
                    <td className="py-4 pr-4">{formatBytes(backup.sizeBytes)}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={3} className="py-8 text-center text-stone-400">
                    Aucune sauvegarde disponible pour le moment.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </section>

      <section className="rounded-xl border border-white/10 bg-white/[0.04] p-6">
        <h2 className="text-xl font-semibold text-white">Planification serveur</h2>
        <p className="mt-2 text-sm text-stone-500">
          À installer sur le serveur pour que la fréquence configurée dans l’admin soit réellement
          exécutée.
        </p>
        <pre className="mt-4 overflow-x-auto rounded-2xl bg-brand-900 p-4 text-sm text-amber-100">
          {status?.scheduler.cronExample}
        </pre>
      </section>
    </section>
  );
};
