import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { fetchConfiguration, updateConfiguration } from '@/features/admin/appointments/api';
import type { WorkingDay } from '@/features/appointments/publicApi';
import { PageContainer } from '@/shared/components/layout/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { WorkingDayConfigurationCard } from '@/features/admin/appointments/components/WorkingDayConfigurationCard';
import { adminAppointmentQueryKeys } from '@/shared/lib/queryKeys';

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
  const [configurationMessage, setConfigurationMessage] = useState<string | null>(null);
  const queryClient = useQueryClient();
  const configurationQuery = useQuery<WorkingDay[], Error>({
    queryKey: adminAppointmentQueryKeys.configuration(),
    queryFn: fetchConfiguration,
  });
  const saveMutation = useMutation({
    mutationFn: updateConfiguration,
    onSuccess: (updated) => {
      const normalized = normalizeDays(updated);
      queryClient.setQueryData(adminAppointmentQueryKeys.configuration(), normalized);
      setConfiguration(normalized);
      setConfigurationMessage('Configuration enregistrée.');
    },
  });
  const configurationError = configurationQuery.error
    ? getHttpErrorMessage(configurationQuery.error, 'Erreur lors du chargement de la configuration')
    : saveMutation.error
      ? getHttpErrorMessage(saveMutation.error, 'Impossible de mettre à jour la configuration')
      : null;

  useEffect(() => {
    if (configurationQuery.data) {
      setConfiguration(normalizeDays(configurationQuery.data));
    }
  }, [configurationQuery.data]);

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
    setConfigurationMessage(null);
    saveMutation.mutate(sanitizedConfiguration);
  };

  return (
    <PageContainer size="admin" title="Configuration des créneaux">
      {configurationError && <FeedbackMessage>{configurationError}</FeedbackMessage>}
      {configurationMessage && (
        <FeedbackMessage variant="success">{configurationMessage}</FeedbackMessage>
      )}

      {configurationQuery.isLoading ? (
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
          disabled={saveMutation.isPending || configurationQuery.isLoading}
          onClick={() => void handleSaveConfiguration()}
        >
          {saveMutation.isPending ? 'Enregistrement...' : 'Enregistrer la configuration'}
        </button>
        <button
          type="button"
          className="catalog-admin-actions__edit"
          onClick={() => {
            setConfigurationMessage(null);
            void configurationQuery.refetch();
          }}
          disabled={saveMutation.isPending}
        >
          Recharger
        </button>
      </div>
    </PageContainer>
  );
};
