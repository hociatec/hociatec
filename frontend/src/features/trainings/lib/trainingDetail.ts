import { formatFrenchTime } from '@/shared/lib/formatters';

export const formatTrainingDuration = (minutes: number) => {
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;
  if (hours > 0 && remainingMinutes > 0) return `${hours} h ${remainingMinutes} min`;
  if (hours > 0) return `${hours} h`;
  return `${minutes} min`;
};

export const calculateEndTime = (date: string, time: string, durationMinutes: number) => {
  if (!date || !time) return null;
  const start = new Date(`${date}T${time}:00`);
  if (Number.isNaN(start.getTime())) return null;
  return formatFrenchTime(new Date(start.getTime() + durationMinutes * 60_000).toISOString());
};

export const calculateLatestStartTime = (dailyEndTime: string, durationMinutes: number) => {
  const end = new Date(`2000-01-01T${dailyEndTime}:00`);
  if (Number.isNaN(end.getTime())) return dailyEndTime;
  return formatFrenchTime(new Date(end.getTime() - durationMinutes * 60_000).toISOString());
};

export const isWeekendDate = (date: string) => {
  const parsed = new Date(`${date}T12:00:00`);
  return !Number.isNaN(parsed.getTime()) && (parsed.getDay() === 0 || parsed.getDay() === 6);
};
