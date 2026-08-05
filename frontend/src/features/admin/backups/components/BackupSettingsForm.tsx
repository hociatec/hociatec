import type { BackupStatusDto } from '../api';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';

type BackupSettingsFormProps = {
  status: BackupStatusDto | null;
  intervalHours: number;
  setIntervalHours: (value: number) => void;
  retentionCount: number;
  setRetentionCount: (value: number) => void;
  enabled: boolean;
  setEnabled: (value: boolean) => void;
  busy: boolean;
  onSubmit: (event: React.FormEvent<HTMLFormElement>) => void;
  onLaunchBackup: () => void;
};

const formatDate = (value?: string | null) => value ? formatOptionalFrenchDateTime(value) : 'Jamais';

export const BackupSettingsForm = ({
  status,
  intervalHours,
  setIntervalHours,
  retentionCount,
  setRetentionCount,
  enabled,
  setEnabled,
  busy,
  onSubmit,
  onLaunchBackup,
}: BackupSettingsFormProps) => (
  <form onSubmit={onSubmit} className="rounded-xl border border-white/10 bg-white/[0.04] p-6">
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
        <strong className="mt-1 block text-white">{formatDate(status?.settings.nextRunAt)}</strong>
      </div>
    </div>

    <div className="mt-6 flex flex-wrap gap-3">
      <button type="submit" disabled={busy} className="btn-primary">
        Enregistrer la configuration
      </button>
      <button type="button" disabled={busy} onClick={onLaunchBackup} className="btn-secondary">
        Sauvegarder maintenant
      </button>
    </div>
  </form>
);
