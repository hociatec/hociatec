export type FeatureFlagName = 'betaProgram';

const parseFeatureFlag = (value: string | undefined, defaultValue: boolean) => {
  if (value === undefined || value.trim() === '') return defaultValue;
  const normalized = value.trim().toLowerCase();
  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;
  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;

  throw new Error(`Feature flag invalide : ${value}`);
};

export const featureFlags: Record<FeatureFlagName, boolean> = {
  betaProgram: parseFeatureFlag(import.meta.env.VITE_FEATURE_BETA_PROGRAM, true),
};

export const isFeatureEnabled = (name: FeatureFlagName) => featureFlags[name];
