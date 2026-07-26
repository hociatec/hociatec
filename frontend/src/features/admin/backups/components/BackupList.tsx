import type { BackupStatusDto } from '../api';

type BackupListProps = {
  backups: BackupStatusDto['backups'];
};

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

export const BackupList = ({ backups }: BackupListProps) => (
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
          {backups.length ? (
            backups.map((backup) => (
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
);
