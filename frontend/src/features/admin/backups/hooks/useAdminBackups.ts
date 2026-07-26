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
      hydrate(await updateBackupSettings({ enabled, intervalHours, retentionCount }));
      setMessage('Configuration des sauvegardes enregistrée.');
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
      hydrate(await runBackupNow());
      setMessage('Sauvegarde terminée et rétention appliquée.');
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
      const maintenance = await updateMaintenanceMode({
        enabled: maintenanceEnabled,
        message: maintenanceMessage,
      });
      setStatus((current) => (current ? { ...current, maintenance } : current));
      setMaintenanceEnabled(maintenance.enabled);
      setMaintenanceMessage(maintenance.message);
      setMessage(maintenance.enabled ? 'Mode maintenance activé.' : 'Mode maintenance désactivé.');
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
