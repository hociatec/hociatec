
import { useAdminBackups } from '../hooks/useAdminBackups';
import { LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { AdminBackupsOverview } from '@/features/admin/backups/components/AdminBackupsOverview';
import { BackupList } from '@/features/admin/backups/components/BackupList';
import { BackupSettingsForm } from '@/features/admin/backups/components/BackupSettingsForm';
import { MaintenanceModeForm } from '@/features/admin/backups/components/MaintenanceModeForm';

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
        <BackupSettingsForm
          status={status}
          intervalHours={intervalHours}
          setIntervalHours={setIntervalHours}
          retentionCount={retentionCount}
          setRetentionCount={setRetentionCount}
          enabled={enabled}
          setEnabled={setEnabled}
          busy={busy}
          onSubmit={submitSettings}
          onLaunchBackup={launchBackup}
        />
        <MaintenanceModeForm
          enabled={maintenanceEnabled}
          setEnabled={setMaintenanceEnabled}
          message={maintenanceMessage}
          setMessage={setMaintenanceMessage}
          busy={busy}
          onSubmit={submitMaintenance}
        />
      </div>

      <BackupList backups={status?.backups ?? []} />

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
