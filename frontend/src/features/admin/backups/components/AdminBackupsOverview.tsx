import type { useAdminBackups } from '@/features/admin/backups/hooks/useAdminBackups';
import { formatOptionalFrenchDateTime } from '@/shared/lib/formatters';
import { AlertTriangle, DatabaseBackup, HardDrive, ShieldCheck } from 'lucide-react';
import type { ReactNode } from 'react';

type BackupStatus = NonNullable<ReturnType<typeof useAdminBackups>['status']>;

const formatDate = (value?: string | null) => value ? formatOptionalFrenchDateTime(value) : 'Jamais';

export const AdminBackupsOverview = ({ status }: { status: BackupStatus }) => <>
  <div className="grid gap-4 md:grid-cols-4"><StatusCard icon={<DatabaseBackup className="h-5 w-5" />} label="Dernière sauvegarde" value={formatDate(status.settings.lastSuccessfulRunAt)} /><StatusCard icon={<ShieldCheck className="h-5 w-5" />} label="Planification" value={status.settings.enabled ? `Toutes les ${status.settings.intervalHours} h` : 'Désactivée'} /><StatusCard icon={<HardDrive className="h-5 w-5" />} label="Sauvegardes conservées" value={`${status.backups.length} / ${status.settings.retentionCount}`} /><StatusCard icon={<AlertTriangle className="h-5 w-5" />} label="Maintenance" value={status.maintenance.enabled ? 'Active' : 'Inactive'} danger={status.maintenance.enabled} /></div>
</>;

const StatusCard = ({ icon, label, value, danger = false }: { icon: ReactNode; label: string; value: string; danger?: boolean }) => <div className={`rounded-2xl border p-5 ${danger ? 'border-red-300/40 bg-red-950/50' : 'border-white/10 bg-white/[0.04]'}`}><div className={danger ? 'text-red-200' : 'text-amber-200'}>{icon}</div><p className="mt-4 text-sm font-medium text-stone-300">{label}</p><strong className="mt-1 block text-lg text-white">{value}</strong></div>;
