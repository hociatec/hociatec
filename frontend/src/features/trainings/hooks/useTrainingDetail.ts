import { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router';
import { enrollTrainingSession, fetchPublicTraining, type TrainingSessionDto } from '../api/trainingsApi';
import { useAuth } from '@/features/auth/publicApi';
import { isWeekendDate } from '../lib/trainingDetail';
import { redirectToTrustedUrl } from '@/shared/lib/redirects';
import { trainingQueryKeys } from '@/features/trainings/queryKeys';

export const useTrainingDetail = () => {
  const { slug = '' } = useParams();
  const navigate = useNavigate();
  const { status } = useAuth();
  const queryClient = useQueryClient();
  const [message, setMessage] = useState<string | null>(null);
  const [pendingSessionId, setPendingSessionId] = useState<number | null>(null);
  const [slotForms, setSlotForms] = useState<Record<number, { date: string; time: string }>>({});
  const trainingQuery = useQuery({
    queryKey: trainingQueryKeys.publicDetail(slug),
    queryFn: ({ signal }) => fetchPublicTraining(slug, { signal }),
    enabled: slug.length > 0,
  });
  const enrollMutation = useMutation({
    mutationFn: ({ sessionId, startsAt }: { sessionId: number; startsAt: string }) =>
      enrollTrainingSession(sessionId, startsAt),
    onSuccess: (response) => {
      if (response.data.checkoutUrl) {
        redirectToTrustedUrl(response.data.checkoutUrl);
        return;
      }

      setMessage(response.message ?? 'Votre inscription a bien été enregistrée.');
      void queryClient.invalidateQueries({ queryKey: trainingQueryKeys.myEnrollments() });
    },
    onError: (err) => {
      setMessage(err instanceof Error ? err.message : 'Inscription impossible.');
    },
    onSettled: () => setPendingSessionId(null),
  });
  const training = trainingQuery.data?.training ?? null;
  const sessions = trainingQuery.data?.sessions ?? [];
  const error = trainingQuery.error instanceof Error ? trainingQuery.error.message : null;
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
    setPendingSessionId(session.id);
    setMessage(null);
    enrollMutation.mutate({ sessionId: session.id, startsAt: `${slot.date}T${slot.time}:00` });
  };
  return {
    slug,
    training,
    sessions,
    loading: trainingQuery.isLoading,
    error,
    retry: trainingQuery.refetch,
    message,
    submittingId: pendingSessionId,
    slotForms,
    updateSlot,
    handleEnroll,
  };
};
