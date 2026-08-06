import {
  requireArray,
  requireNumber,
  requireRecord,
  requireString,
  optionalString,
} from '@/shared/lib/contractValidation';
import { omitUndefinedProperties } from '@/shared/lib/object';
import type { AuthUser } from '@/shared/types/auth';

const stringOrEmpty = (value: unknown): string => optionalString(value) ?? '';

export const parseAuthUser = (value: unknown): AuthUser => {
  const user = requireRecord(value);

  return omitUndefinedProperties({
    id: requireNumber(user.id),
    email: requireString(user.email),
    firstName: stringOrEmpty(user.firstName),
    lastName: stringOrEmpty(user.lastName),
    address: stringOrEmpty(user.address),
    postalCode: stringOrEmpty(user.postalCode),
    city: stringOrEmpty(user.city),
    birthDate: stringOrEmpty(user.birthDate),
    phoneNumber: stringOrEmpty(user.phoneNumber),
    gender: stringOrEmpty(user.gender),
    roles: requireArray(user.roles).map((role) => requireString(role)),
    permissions:
      user.permissions === undefined
        ? []
        : requireArray(user.permissions).map((permission) => requireString(permission)),
    communicationPreferences:
      user.communicationPreferences === undefined
        ? undefined
        : requireArray(user.communicationPreferences).map((preference) => optionalString(preference) ?? ''),
  });
};
