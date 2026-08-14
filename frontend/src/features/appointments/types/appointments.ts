export interface Prestation {
  id: number;
  name: string;
  durationMinutes: number;
  priceCents: number;
}

export interface WorkingDay {
  dayOfWeek: number; // 0 (lundi) .. 6 (dimanche)
  dayLabel?: string;
  isWorkingDay: boolean;
  startTime: string | null; // HH:mm
  endTime: string | null; // HH:mm
  breaks: Array<{ start: string; end: string }>;
}

export interface AvailabilitySlot {
  start: string; // ISO
  end: string; // ISO
}

export interface AppointmentPayload {
  prestationId: number;
  startAt: string; // ISO
}

export interface AppointmentItem {
  id: number;
  startAt: string;
  endAt: string;
  status?: string;
  statusCode?: string;
  isCancelable?: boolean;
  isReschedulable?: boolean;
  prestation: Prestation;
}
