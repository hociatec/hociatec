import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  fetchBackupStatus,
  runBackupNow,
  updateBackupSettings,
  updateMaintenanceMode,
  type BackupStatusDto,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { adminBackupQueryKeys } from '@/features/admin/backups/queryKeys';

export const useAdminBackups = () => {
  const queryClient = useQueryClient();
  const [intervalHours, setIntervalHours] = useState(24);
  const [retentionCount, setRetentionCount] = useState(7);
  const [enabled, setEnabled] = useState(false);
  const [maintenanceEnabled, setMaintenanceEnabled] = useState(false);
  const [maintenanceMessage, setMaintenanceMessage] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const statusQuery = useQuery<BackupStatusDto, Error>({
    queryKey: adminBackupQueryKeys.status(),
    queryFn: fetchBackupStatus,
  });
  const status = statusQuery.data ?? null;
  const hydrate = (data: BackupStatusDto) => {
    queryClient.setQueryData(adminBackupQueryKeys.status(), data);
    setIntervalHours(data.settings.intervalHours);
    setRetentionCount(data.settings.retentionCount);
    setEnabled(data.settings.enabled);
    setMaintenanceEnabled(data.maintenance.enabled);
    setMaintenanceMessage(data.maintenance.message);
  };

  useEffect(() => {
    if (statusQuery.data) hydrate(statusQuery.data);
  }, [statusQuery.data]);

  const settingsMutation = useMutation({
    mutationFn: () => updateBackupSettings({ enabled, intervalHours, retentionCount }),
    onSuccess: (response) => {
      hydrate(response.data);
      setMessage(response.message ?? 'La configuration des sauvegardes a bien été enregistrée.');
    },
    onError: (e) => setError(getHttpErrorMessage(e, 'Impossible de sauvegarder la configuration.')),
  });
  const backupMutation = useMutation({
    mutationFn: runBackupNow,
    onSuccess: (response) => {
      hydrate(response.data);
      setMessage(response.message ?? 'La sauvegarde a bien été exécutée.');
    },
    onError: (e) => setError(getHttpErrorMessage(e, 'Impossible de lancer la sauvegarde.')),
  });
  const maintenanceMutation = useMutation({
    mutationFn: () =>
      updateMaintenanceMode({
        enabled: maintenanceEnabled,
        message: maintenanceMessage,
      }),
    onSuccess: (response) => {
      queryClient.setQueryData<BackupStatusDto>(adminBackupQueryKeys.status(), (current) =>
        current ? { ...current, maintenance: response.data } : current,
      );
      setMaintenanceEnabled(response.data.enabled);
      setMaintenanceMessage(response.data.message);
      setMessage(response.message ?? (response.data.enabled ? 'Le mode maintenance a été activé.' : 'Le mode maintenance a été désactivé.'));
    },
    onError: (e) => setError(getHttpErrorMessage(e, 'Impossible de modifier le mode maintenance.')),
  });

  const submitSettings = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setMessage(null);
    settingsMutation.mutate();
  };
  const launchBackup = async () => {
    setError(null);
    setMessage(null);
    backupMutation.mutate();
  };
  const submitMaintenance = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setMessage(null);
    maintenanceMutation.mutate();
  };
  return {
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
    loading: statusQuery.isLoading,
    busy: settingsMutation.isPending || backupMutation.isPending || maintenanceMutation.isPending,
    message,
    error:
      error ??
      (statusQuery.error
        ? getHttpErrorMessage(statusQuery.error, 'Impossible de charger les sauvegardes.')
        : null),
    submitSettings,
    launchBackup,
    submitMaintenance,
  };
};
