import type { BetaProfileDto } from '@/features/betaTest/api/betaApi';

export type EditableProfile = {
  motivation: string;
  testingExperience: string[];
  bugDescriptionAbility: string[];
  technicalKnowledge: string[];
  availability: string[];
  accessibilityNeed: string;
  assistiveTools: string[];
  devices: string[];
  browsers: string[];
  testingTypes: string[];
  betaConsent: boolean;
};

export const emptyBetaProfileForm = (): EditableProfile => ({
  motivation: '',
  testingExperience: [],
  bugDescriptionAbility: [],
  technicalKnowledge: [],
  availability: ['flexible'],
  accessibilityNeed: 'none',
  assistiveTools: [],
  devices: ['windows'],
  browsers: ['chrome'],
  testingTypes: ['bugs'],
  betaConsent: true,
});

export const listFromProfile = (value: unknown, fallback: string[] = []) => Array.isArray(value)
  ? value.filter((item): item is string => typeof item === 'string')
  : fallback;

export const buildBetaProfileForm = (profile: BetaProfileDto): EditableProfile => ({
  motivation: String(profile.motivation ?? ''),
  testingExperience: listFromProfile(profile.testingExperience),
  bugDescriptionAbility: listFromProfile(profile.bugDescriptionAbility),
  technicalKnowledge: listFromProfile(profile.technicalKnowledge),
  availability: listFromProfile(profile.availability, ['flexible']),
  accessibilityNeed: String(profile.accessibilityNeed ?? 'none'),
  assistiveTools: listFromProfile(profile.assistiveTools),
  devices: listFromProfile(profile.devices, ['windows']),
  browsers: listFromProfile(profile.browsers, ['chrome']),
  testingTypes: listFromProfile(profile.testingTypes, ['bugs']),
  betaConsent: true,
});

export const normalizeCheckboxSelection = (
  name: keyof EditableProfile,
  value: string,
  checked: boolean,
  current: string[],
) => {
  const next = checked ? [...current, value] : current.filter((item) => item !== value);

  if (name !== 'assistiveTools') {
    return next;
  }

  if (checked && value === 'none') {
    return ['none'];
  }

  return next.filter((item) => item !== 'none');
};

export const isBetaProfileComplete = (form: EditableProfile) => Boolean(
  form.motivation.trim()
    && form.testingExperience.length
    && form.bugDescriptionAbility.length
    && form.technicalKnowledge.length
    && form.availability.length
    && form.assistiveTools.length
    && form.devices.length
    && form.browsers.length
    && form.testingTypes.length,
);
