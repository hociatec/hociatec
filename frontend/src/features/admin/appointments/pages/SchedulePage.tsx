import { useEffect, useMemo, useState } from 'react';

import { fetchConfiguration, updateConfiguration } from '@/features/admin/appointments/api';
import type { WorkingDay } from '@/features/appointments/types/appointments';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { WorkingDayConfigurationCard } from '@/features/admin/appointments/components/WorkingDayConfigurationCard';

const normalizeDays = (days: WorkingDay[]): WorkingDay[] =>
  [...days]
    .map((day) => ({
      ...day,
      breaks: day.breaks?.map((pause) => ({ start: pause.start, end: pause.end })) ?? [],
    }))
    .sort((a, b) => a.dayOfWeek - b.dayOfWeek);

export const SchedulePage = () => {
  useDocumentTitle('Admin - Creneaux');

  const [configuration, setConfiguration] = useState<WorkingDay[]>([]);
  const [configurationLoading, setConfigurationLoading] = useState(true);
  const [configurationMessage, setConfigurationMessage] = useState<string | null>(null);
  const [configurationError, setConfigurationError] = useState<string | null>(null);
  const [savingConfiguration, setSavingConfiguration] = useState(false);

  useEffect(() => {
    void loadConfiguration();
  }, []);

  const loadConfiguration = async () => {
    setConfigurationLoading(true);
    setConfigurationError(null);
    setConfigurationMessage(null);

    try {
      const days = await fetchConfiguration();
      setConfiguration(normalizeDays(days));
    } catch (error) {
      setConfigurationError(
        getHttpErrorMessage(error, 'Erreur lors du chargement de la configuration'),
      );
    } finally {
      setConfigurationLoading(false);
    }
  };

  const updateDay = (dayOfWeek: number, updater: (current: WorkingDay) => WorkingDay) => {
    setConfiguration((current) =>
      current.map((day) => (day.dayOfWeek === dayOfWeek ? updater(day) : day)),
    );
  };

  const sanitizedConfiguration = useMemo<WorkingDay[]>(
    () =>
      configuration.map((day) => ({
        dayOfWeek: day.dayOfWeek,
        isWorkingDay: day.isWorkingDay,
        startTime: day.isWorkingDay ? day.startTime || null : null,
        endTime: day.isWorkingDay ? day.endTime || null : null,
        breaks: day.isWorkingDay
          ? day.breaks
              .filter((pause) => pause.start && pause.end)
              .map((pause) => ({ start: pause.start, end: pause.end }))
          : [],
      })),
    [configuration],
  );

  const handleSaveConfiguration = async () => {
    setConfigurationError(null);
    setConfigurationMessage(null);
    setSavingConfiguration(true);

    try {
      const updated = await updateConfiguration(sanitizedConfiguration);
      setConfiguration(normalizeDays(updated));
      setConfigurationMessage('Configuration enregistrée.');
    } catch (error) {
      setConfigurationError(
        getHttpErrorMessage(error, 'Impossible de mettre à jour la configuration'),
      );
    } finally {
      setSavingConfiguration(false);
    }
  };

  return (
    <PageContainer size="admin" title="Configuration des créneaux">
      {configurationError && <FeedbackMessage>{configurationError}</FeedbackMessage>}
      {configurationMessage && (
        <FeedbackMessage variant="success">{configurationMessage}</FeedbackMessage>
      )}

      {configurationLoading ? (
        <LoadingState>Chargement de la configuration...</LoadingState>
      ) : (
        <div className="grid gap-4">
          {configuration.map((day) => (
            <WorkingDayConfigurationCard key={day.dayOfWeek} day={day} updateDay={updateDay} />
          ))}
        </div>
      )}

      <div className="mt-4 flex flex-wrap gap-3">
        <button
          type="button"
          className="register-form__submit"
          disabled={savingConfiguration || configurationLoading}
          onClick={() => void handleSaveConfiguration()}
        >
          {savingConfiguration ? 'Enregistrement...' : 'Enregistrer la configuration'}
        </button>
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => void loadConfiguration()}
          disabled={savingConfiguration}
        >
          Recharger
        </button>
      </div>
    </PageContainer>
  );
};
