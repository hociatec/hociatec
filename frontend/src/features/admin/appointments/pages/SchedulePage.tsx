import { Fragment, useEffect, useMemo, useState } from 'react';

import { fetchConfiguration, updateConfiguration } from '@/features/admin/appointments/api';
import type { WorkingDay } from '@/features/appointments/types';
import { PageContainer } from '@/shared/components/PageContainer';
import { FeedbackMessage, LoadingState } from '@/shared/components/ui/page-state';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';

const DAY_LABELS: Record<number, string> = {
  0: 'Lundi',
  1: 'Mardi',
  2: 'Mercredi',
  3: 'Jeudi',
  4: 'Vendredi',
  5: 'Samedi',
  6: 'Dimanche',
};

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
      setConfigurationError(getHttpErrorMessage(error, 'Erreur lors du chargement de la configuration'));
    } finally {
      setConfigurationLoading(false);
    }
  };

  const updateDay = (dayOfWeek: number, updater: (current: WorkingDay) => WorkingDay) => {
    setConfiguration((current) =>
      current.map((day) => (day.dayOfWeek === dayOfWeek ? updater(day) : day))
    );
  };

  const sanitizedConfiguration = useMemo(
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
    [configuration]
  );

  const handleSaveConfiguration = async () => {
    setConfigurationError(null);
    setConfigurationMessage(null);
    setSavingConfiguration(true);

    try {
      const updated = await updateConfiguration(sanitizedConfiguration as WorkingDay[]);
      setConfiguration(normalizeDays(updated));
      setConfigurationMessage('Configuration enregistrée.');
    } catch (error) {
      setConfigurationError(getHttpErrorMessage(error, 'Impossible de mettre à jour la configuration'));
    } finally {
      setSavingConfiguration(false);
    }
  };

  return (
    <PageContainer size="admin" title="Configuration des créneaux">
      {configurationError && <FeedbackMessage>{configurationError}</FeedbackMessage>}
      {configurationMessage && <FeedbackMessage variant="success">{configurationMessage}</FeedbackMessage>}

      {configurationLoading ? (
        <LoadingState>Chargement de la configuration...</LoadingState>
      ) : (
        <div className="grid gap-4">
          {configuration.map((day) => (
            <div key={day.dayOfWeek} className="register-form-card form-card-grid">
              <label className="booking__checkbox">
                <input
                  type="checkbox"
                  checked={day.isWorkingDay}
                  onChange={(event) =>
                    updateDay(day.dayOfWeek, (current) => ({
                      ...current,
                      isWorkingDay: event.target.checked,
                      startTime: event.target.checked ? current.startTime ?? '09:00' : null,
                      endTime: event.target.checked ? current.endTime ?? '18:00' : null,
                      breaks: event.target.checked ? current.breaks ?? [] : [],
                    }))
                  }
                />
                <strong>{DAY_LABELS[day.dayOfWeek]}</strong>
              </label>

              {day.isWorkingDay && (
                <Fragment>
                  <div className="grid gap-4 md:grid-cols-2">
                    <label className="register-form__field">
                      <span className="register-form__label">Début</span>
                      <input
                        type="time"
                        className="register-form__input"
                        value={day.startTime ?? ''}
                        onChange={(event) =>
                          updateDay(day.dayOfWeek, (current) => ({
                            ...current,
                            startTime: event.target.value || null,
                          }))
                        }
                      />
                    </label>
                    <label className="register-form__field">
                      <span className="register-form__label">Fin</span>
                      <input
                        type="time"
                        className="register-form__input"
                        value={day.endTime ?? ''}
                        onChange={(event) =>
                          updateDay(day.dayOfWeek, (current) => ({
                            ...current,
                            endTime: event.target.value || null,
                          }))
                        }
                      />
                    </label>
                  </div>

                  <div className="grid gap-2">
                    <span className="register-form__label">Pauses</span>
                    {day.breaks.length === 0 && <p className="muted">Aucune pause définie.</p>}
                    {day.breaks.map((pause, index) => (
                      <div
                        key={`${day.dayOfWeek}-${index}`}
                        className="flex flex-wrap items-center gap-3"
                      >
                        <input
                          type="time"
                          className="register-form__input"
                          value={pause.start}
                          onChange={(event) =>
                            updateDay(day.dayOfWeek, (current) => ({
                              ...current,
                              breaks: current.breaks.map((slot, idx) =>
                                idx === index ? { ...slot, start: event.target.value } : slot
                              ),
                            }))
                          }
                        />
                        <span>à</span>
                        <input
                          type="time"
                          className="register-form__input"
                          value={pause.end}
                          onChange={(event) =>
                            updateDay(day.dayOfWeek, (current) => ({
                              ...current,
                              breaks: current.breaks.map((slot, idx) =>
                                idx === index ? { ...slot, end: event.target.value } : slot
                              ),
                            }))
                          }
                        />
                        <button
                          type="button"
                          className="catalog-admin-actions__delete"
                          onClick={() =>
                            updateDay(day.dayOfWeek, (current) => ({
                              ...current,
                              breaks: current.breaks.filter((_, idx) => idx !== index),
                            }))
                          }
                        >
                          Supprimer
                        </button>
                      </div>
                    ))}
                    <button
                      type="button"
                      className="catalog-admin-actions__edit w-fit"
                      onClick={() =>
                        updateDay(day.dayOfWeek, (current) => ({
                          ...current,
                          breaks: [
                            ...current.breaks,
                            { start: current.startTime ?? '12:00', end: current.endTime ?? '13:00' },
                          ],
                        }))
                      }
                    >
                      Ajouter une pause
                    </button>
                  </div>
                </Fragment>
              )}
            </div>
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
