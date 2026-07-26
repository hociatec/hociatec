export type CampaignCriteriaForm = {
  segmentKey: string;
  minimumOrders: string;
  inactiveDays: string;
  registeredDays: string;
  recentDays: string;
  minimumTotalCents: string;
  minimumPendingReviews: string;
};

export const buildCampaignCriteria = (form: CampaignCriteriaForm): Record<string, string | number | boolean> => {
  const criteria: Record<string, string | number | boolean> = {};

  if (form.segmentKey === 'customers_with_orders' || form.segmentKey === 'loyal_customers') {
    criteria.minimumOrders = Number.parseInt(form.minimumOrders, 10) || 1;
  }
  if (form.segmentKey === 'inactive_customers') {
    criteria.inactiveDays = Number.parseInt(form.inactiveDays, 10) || 90;
  }
  if (form.segmentKey === 'recent_verified_users' || form.segmentKey === 'verified_without_orders_recent') {
    criteria.registeredDays = Number.parseInt(form.registeredDays, 10) || 30;
  }
  if (form.segmentKey === 'recent_customers') {
    criteria.recentDays = Number.parseInt(form.recentDays, 10) || 30;
  }
  if (form.segmentKey === 'high_value_customers') {
    criteria.minimumTotalCents = Number.parseInt(form.minimumTotalCents, 10) || 50000;
  }
  if (form.segmentKey === 'customers_with_pending_reviews') {
    criteria.minimumPendingReviews = Number.parseInt(form.minimumPendingReviews, 10) || 2;
  }

  return criteria;
};
