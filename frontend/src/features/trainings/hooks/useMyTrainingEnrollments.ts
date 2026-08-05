import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router';
import { fetchMyTrainingEnrollments, type TrainingEnrollmentDto } from '../api/trainingsApi';
import { trainingQueryKeys } from '@/shared/lib/queryKeys';

export const useMyTrainingEnrollments = () => {
  const { enrollmentId } = useParams();
  const query = useQuery<TrainingEnrollmentDto[], Error>({
    queryKey: trainingQueryKeys.myEnrollments(),
    queryFn: fetchMyTrainingEnrollments,
  });
  const items = query.data ?? [];
  const enrollment = useMemo(
    () => items.find((item) => item.id === Number(enrollmentId)) ?? null,
    [items, enrollmentId],
  );
  return {
    items,
    enrollment,
    loading: query.isLoading,
    error: query.error?.message ?? null,
  };
};
