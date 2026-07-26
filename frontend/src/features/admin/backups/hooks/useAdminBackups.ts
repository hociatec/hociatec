import { useEffect, useState } from 'react';
import {
  fetchBackupStatus,
  runBackupNow,
  updateBackupSettings,
  updateMaintenanceMode,
  type BackupStatusDto,
} from '../api';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
export const useAdminBackups = () => {
  const [status, setStatus] = useState<BackupStatusDto | null>(null);
  const [intervalHours, setIntervalHours] = useState(24);
  const [retentionCount, setRetentionCount] = useState(7);
  const [enabled, setEnabled] = useState(false);
  const [maintenanceEnabled, setMaintenanceEnabled] = useState(false);
  const [maintenanceMessage, setMaintenanceMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const hydrate = (data: BackupStatusDto) => {
    setStatus(data);
    setIntervalHours(data.settings.intervalHours);
    setRetentionCount(data.settings.retentionCount);
    setEnabled(data.settings.enabled);
    setMaintenanceEnabled(data.maintenance.enabled);
    setMaintenanceMessage(data.maintenance.message);
  };
  useEffect(() => {
    void fetchBackupStatus()
      .then(hydrate)
      .catch((e) => setError(getHttpErrorMessage(e, 'Impossible de charger les sauvegardes.')))
      .finally(() => setLoading(false));
  }, []);
  const submitSettings = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setBusy(true);
    setError(null);
    setMessage(null);
    try {
      const response = await updateBackupSettings({ enabled, intervalHours, retentionCount });
      hydrate(response.data);
      setMessage(response.message ?? 'La configuration des sauvegardes a bien été enregistrée.');
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Impossible de sauvegarder la configuration.'));
    } finally {
      setBusy(false);
    }
  };
  const launchBackup = async () => {
    setBusy(true);
    setError(null);
    setMessage(null);
    try {
      const response = await runBackupNow();
      hydrate(response.data);
      setMessage(response.message ?? 'La sauvegarde a bien été exécutée.');
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Impossible de lancer la sauvegarde.'));
    } finally {
      setBusy(false);
    }
  };
  const submitMaintenance = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setBusy(true);
    setError(null);
    setMessage(null);
    try {
      const response = await updateMaintenanceMode({
        enabled: maintenanceEnabled,
        message: maintenanceMessage,
      });
      setStatus((current) => (current ? { ...current, maintenance: response.data } : current));
      setMaintenanceEnabled(response.data.enabled);
      setMaintenanceMessage(response.data.message);
      setMessage(response.message ?? (response.data.enabled ? 'Le mode maintenance a été activé.' : 'Le mode maintenance a été désactivé.'));
    } catch (e) {
      setError(getHttpErrorMessage(e, 'Impossible de modifier le mode maintenance.'));
    } finally {
      setBusy(false);
    }
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
    loading,
    busy,
    message,
    error,
    submitSettings,
    launchBackup,
    submitMaintenance,
  };
};
