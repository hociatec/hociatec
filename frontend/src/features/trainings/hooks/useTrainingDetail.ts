import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
  enrollTrainingSession,
  fetchPublicTraining,
  type TrainingDto,
  type TrainingSessionDto,
} from '../api/trainingsApi';
import { useAuth } from '@/features/auth/hooks/useAuth';
import { isWeekendDate } from '../lib/trainingDetail';

export const useTrainingDetail = () => {
  const { slug = '' } = useParams();
  const navigate = useNavigate();
  const { status } = useAuth();
  const [training, setTraining] = useState<TrainingDto | null>(null);
  const [sessions, setSessions] = useState<TrainingSessionDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [submittingId, setSubmittingId] = useState<number | null>(null);
  const [slotForms, setSlotForms] = useState<Record<number, { date: string; time: string }>>({});
  useEffect(() => {
    setLoading(true);
    setError(null);
    void fetchPublicTraining(slug)
      .then((data) => {
        setTraining(data.training);
        setSessions(data.sessions);
      })
      .catch((err: Error) => setError(err.message || 'Formation introuvable.'))
      .finally(() => setLoading(false));
  }, [slug]);
  const updateSlot = (
    sessionId: number,
    field: 'date' | 'time',
    value: string,
    defaults?: { date?: string; time?: string },
  ) =>
    setSlotForms((previous) => ({
      ...previous,
      [sessionId]: {
        date: previous[sessionId]?.date ?? defaults?.date ?? '',
        time: previous[sessionId]?.time ?? defaults?.time ?? '',
        [field]: value,
      },
    }));
  const handleEnroll = async (session: TrainingSessionDto) => {
    if (status !== 'authenticated') {
      navigate('/login', { state: { redirectTo: `/formations/${slug}` } });
      return;
    }
    const slot = slotForms[session.id];
    if (!slot?.date || !slot?.time) {
      setMessage('Choisissez une date et une heure de début.');
      return;
    }
    if (!session.includeWeekends && isWeekendDate(slot.date)) {
      setMessage('Cette session est réservable uniquement du lundi au vendredi.');
      return;
    }
    setSubmittingId(session.id);
    setMessage(null);
    try {
      const response = await enrollTrainingSession(session.id, `${slot.date}T${slot.time}:00`);
      if (response.data.checkoutUrl) {
        window.location.href = response.data.checkoutUrl;
        return;
      }
      setMessage(response.message ?? 'Votre inscription a bien été enregistrée.');
    } catch (err) {
      setMessage(err instanceof Error ? err.message : 'Inscription impossible.');
    } finally {
      setSubmittingId(null);
    }
  };
  return {
    slug,
    training,
    sessions,
    loading,
    error,
    message,
    submittingId,
    slotForms,
    updateSlot,
    handleEnroll,
  };
};
