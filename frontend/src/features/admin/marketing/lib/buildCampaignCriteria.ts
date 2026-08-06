import { parseNonNegativeInteger } from '@/shared/lib/parsers';

export type CampaignCriteriaForm = {
  segmentKey: string;
  minimumOrders: string;
  inactiveDays: string;
  registeredDays: string;
  recentDays: string;
  minimumTotalCents: string;
  minimumPendingReviews: string;
};

export const buildCampaignCriteria = (
  form: CampaignCriteriaForm,
): Record<string, string | number | boolean> => {
  const criteria: Record<string, string | number | boolean> = {};

  if (form.segmentKey === 'customers_with_orders' || form.segmentKey === 'loyal_customers') {
    criteria.minimumOrders = parseNonNegativeInteger(form.minimumOrders, 1);
  }
  if (form.segmentKey === 'inactive_customers') {
    criteria.inactiveDays = parseNonNegativeInteger(form.inactiveDays, 90);
  }
  if (
    form.segmentKey === 'recent_verified_users' ||
    form.segmentKey === 'verified_without_orders_recent'
  ) {
    criteria.registeredDays = parseNonNegativeInteger(form.registeredDays, 30);
  }
  if (form.segmentKey === 'recent_customers') {
    criteria.recentDays = parseNonNegativeInteger(form.recentDays, 30);
  }
  if (form.segmentKey === 'high_value_customers') {
    criteria.minimumTotalCents = parseNonNegativeInteger(form.minimumTotalCents, 50000);
  }
  if (form.segmentKey === 'customers_with_pending_reviews') {
    criteria.minimumPendingReviews = parseNonNegativeInteger(form.minimumPendingReviews, 2);
  }

  return criteria;
};
