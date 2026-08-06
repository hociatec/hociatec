import { normalizeSearchText } from '@/shared/lib/searchText';

export type FeatureFlagName = 'betaProgram';

const parseFeatureFlag = (value: string | undefined, defaultValue: boolean) => {
  const normalized = value === undefined ? '' : normalizeSearchText(value).trim();
  if (normalized === '') return defaultValue;
  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;
  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;

  throw new Error(`Feature flag invalide : ${value}`);
};

export const featureFlags: Record<FeatureFlagName, boolean> = {
  betaProgram: parseFeatureFlag(import.meta.env.VITE_FEATURE_BETA_PROGRAM, true),
};

export const isFeatureEnabled = (name: FeatureFlagName) => featureFlags[name];
