import { Fragment, useEffect, useMemo, useState } from 'react';

import { fetchConfiguration, updateConfiguration } from '@/features/admin/appointments/api';
import { useRequireAdmin } from '@/features/admin/hooks/useRequireAdmin';
import type { WorkingDay } from '@/features/appointments/types';
import { PageContainer } from '@/shared/components/PageContainer';
import { useDocumentTitle } from '@/shared/hooks/useDocumentTitle';

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

  const { isAdmin, loading: guardLoading } = useRequireAdmin();
  const [configuration, setConfiguration] = useState<WorkingDay[]>([]);
  const [configurationLoading, setConfigurationLoading] = useState(true);
  const [configurationMessage, setConfigurationMessage] = useState<string | null>(null);
  const [configurationError, setConfigurationError] = useState<string | null>(null);
  const [savingConfiguration, setSavingConfiguration] = useState(false);

  useEffect(() => {
    if (!isAdmin) {
      return;
    }

    void loadConfiguration();
  }, [isAdmin]);

  const loadConfiguration = async () => {
    setConfigurationLoading(true);
    setConfigurationError(null);
    setConfigurationMessage(null);

    try {
      const days = await fetchConfiguration();
      setConfiguration(normalizeDays(days));
    } catch (error: any) {
      setConfigurationError(error?.message || 'Erreur lors du chargement de la configuration');
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
      setConfigurationMessage('Configuration enregistree.');
    } catch (error: any) {
      setConfigurationError(error?.message || 'Impossible de mettre a jour la configuration');
    } finally {
      setSavingConfiguration(false);
    }
  };

  if (guardLoading) {
    return (
      <PageContainer title="Configuration des creneaux">
        <p className="muted">Verification des droits...</p>
      </PageContainer>
    );
  }

  if (!isAdmin) {
    return (
      <PageContainer title="Configuration des creneaux">
        <div className="register-form__alert">Acces restreint aux administrateurs.</div>
      </PageContainer>
    );
  }

  return (
    <PageContainer title="Configuration des creneaux">
      {configurationError && <div className="register-form__alert">{configurationError}</div>}
      {configurationMessage && (
        <div className="register-form__alert" style={{ background: '#ecfdf5', color: '#047857' }}>
          {configurationMessage}
        </div>
      )}

      {configurationLoading ? (
        <p className="muted">Chargement de la configuration...</p>
      ) : (
        <div style={{ display: 'grid', gap: 16 }}>
          {configuration.map((day) => (
            <div key={day.dayOfWeek} className="register-form-card" style={{ display: 'grid', gap: 12 }}>
              <label style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
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
                  <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap' }}>
                    <label className="register-form__field" style={{ flex: '1 1 160px' }}>
                      <span className="register-form__label">Debut</span>
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
                    <label className="register-form__field" style={{ flex: '1 1 160px' }}>
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

                  <div style={{ display: 'grid', gap: 8 }}>
                    <span className="register-form__label">Pauses</span>
                    {day.breaks.length === 0 && <p className="muted">Aucune pause definie.</p>}
                    {day.breaks.map((pause, index) => (
                      <div
                        key={`${day.dayOfWeek}-${index}`}
                        style={{ display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}
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
                        <span>a</span>
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
                          className="register-form__submit"
                          style={{ background: '#fee2e2', color: '#991b1b' }}
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
                      className="register-form__submit"
                      style={{ background: '#e5e7eb', color: '#111827', width: 'fit-content' }}
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

      <div style={{ marginTop: 16 }}>
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
          className="register-form__submit"
          style={{ marginLeft: 12, background: '#e5e7eb', color: '#111827' }}
          onClick={() => void loadConfiguration()}
          disabled={savingConfiguration}
        >
          Recharger
        </button>
      </div>
    </PageContainer>
  );
};
