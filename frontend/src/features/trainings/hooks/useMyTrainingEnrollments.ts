import { useEffect, useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import { fetchMyTrainingEnrollments, type TrainingEnrollmentDto } from '../api/trainingsApi';

export const useMyTrainingEnrollments = () => {
  const { enrollmentId } = useParams();
  const [items, setItems] = useState<TrainingEnrollmentDto[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => { setLoading(true); setError(null); void fetchMyTrainingEnrollments().then(setItems).catch((err: Error) => setError(err.message || 'Chargement impossible.')).finally(() => setLoading(false)); }, []);
  const enrollment = useMemo(() => items.find((item) => item.id === Number(enrollmentId)) ?? null, [items, enrollmentId]);
  return { items, enrollment, loading, error };
};
