export type TrainingFormat = 'onsite' | 'remote';
export type TrainingEnrollmentStatus =
  'pending_payment' | 'paid' | 'confirmed' | 'completed' | 'cancelled';

export interface TrainingCategoryReference {
  id: number | null;
  name: string;
  slug: string;
}

export interface TrainingFormatOption {
  value: string;
  label: string;
}

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
  categoryDetails: TrainingCategoryReference | null;
  availableFormatDetails: TrainingFormatOption[];
}

export interface TrainingSessionDto {
  id: number;
  training: TrainingDto;
  format: TrainingFormat;
  formatLabel: string;
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
  statusLabel: string;
}

export interface TrainingEnrollmentDto {
  id: number;
  status: TrainingEnrollmentStatus;
  statusLabel: string;
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
