export type TrainingFormat = 'onsite' | 'remote';
export type TrainingEnrollmentStatus =
  'pending_payment' | 'paid' | 'confirmed' | 'completed' | 'cancelled';

export const FALLBACK_TRAINING_CATEGORIES = [
  { value: 'bases', label: 'Bases numériques' },
  { value: 'securite', label: 'Sécurité et sauvegarde' },
  { value: 'productivite', label: 'Productivité' },
  { value: 'web', label: 'Web et présence en ligne' },
  { value: 'ia', label: 'Intelligence artificielle' },
  { value: 'entreprise', label: 'Entreprise' },
  { value: 'general', label: 'Général' },
] as const;

export interface TrainingCategoryDto {
  id: number;
  name: string;
  slug: string;
  position: number;
  isActive: boolean;
}

export interface TrainingCategoryInput {
  name: string;
  slug?: string;
  position: number;
  isActive: boolean;
}

export interface TrainingRoadmapItemDto {
  id: number;
  position: number;
  title: string;
}

export interface TrainingDto {
  id: number;
  title: string;
  slug: string;
  shortDescription: string | null;
  objective: string | null;
  audience: string | null;
  category: string;
  durationMinutes: number;
  priceCents: number;
  availableFormats: TrainingFormat[];
  isActive: boolean;
  roadmap: TrainingRoadmapItemDto[];
}

export interface TrainingSessionDto {
  id: number;
  training: TrainingDto;
  format: TrainingFormat;
  startsAt: string;
  endsAt: string;
  dailyStartTime: string;
  dailyEndTime: string;
  includeWeekends: boolean;
  location: string | null;
  meetingUrl: string | null;
  capacity: number;
  enrolledCount: number;
  remainingSeats: number;
  status: string;
}

export interface TrainingEnrollmentDto {
  id: number;
  status: TrainingEnrollmentStatus | string;
  priceCents: number;
  scheduledStartsAt: string;
  scheduledEndsAt: string;
  paidAt: string | null;
  stripeSessionId?: string | null;
  checkoutUrl?: string | null;
  createdAt: string;
  session: TrainingSessionDto;
}

export interface TrainingInput {
  title: string;
  slug?: string;
  shortDescription?: string | null;
  objective?: string | null;
  audience?: string | null;
  category: string;
  durationMinutes: number;
  priceCents: number;
  availableFormats: TrainingFormat[];
  isActive: boolean;
  roadmap: string[];
}

export interface TrainingSessionInput {
  trainingId: number;
  format: TrainingFormat;
  startsAt: string;
  endsAt: string;
  dailyStartTime: string;
  dailyEndTime: string;
  includeWeekends: boolean;
  location?: string | null;
  meetingUrl?: string | null;
  capacity: number;
  status: string;
}

export const formatTrainingFormat = (format?: string | null) =>
  format === 'remote' ? 'Distanciel' : format === 'onsite' ? 'Présentiel' : '-';

export const formatTrainingCategory = (category?: string | null) =>
  FALLBACK_TRAINING_CATEGORIES.find((item) => item.value === category)?.label ??
  category ??
  'Général';

export const formatTrainingEnrollmentStatus = (status?: string | null) => {
  const labels: Record<string, string> = {
    pending_payment: 'Paiement en attente',
    paid: 'Payée',
    confirmed: 'Confirmée',
    completed: 'Terminée',
    cancelled: 'Annulée',
  };

  return status ? (labels[status] ?? status) : '-';
};
