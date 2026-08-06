import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useParams } from 'react-router';
import { fetchMyTrainingEnrollments, type TrainingEnrollmentDto } from '../api/trainingsApi';
import { trainingQueryKeys } from '@/features/trainings/queryKeys';
import type { PaginatedResult } from '@/shared/types/api';

export const useMyTrainingEnrollments = () => {
  const { enrollmentId } = useParams();
  const [page, setPage] = useState(1);
  const query = useQuery<PaginatedResult<TrainingEnrollmentDto>, Error>({
    queryKey: [...trainingQueryKeys.myEnrollments(), { page }],
    queryFn: () => fetchMyTrainingEnrollments(page, 10),
  });
  const items = query.data?.items ?? [];
  const enrollment = useMemo(
    () => items.find((item) => item.id === Number(enrollmentId)) ?? null,
    [items, enrollmentId],
  );
  return {
    items,
    pagination: query.data?.meta ?? { page, perPage: 10, total: 0, totalPages: 1 },
    setPage,
    enrollment,
    loading: query.isLoading,
    error: query.error?.message ?? null,
  };
};
