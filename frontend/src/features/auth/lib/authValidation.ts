import {
  requireArray,
  requireNumber,
  requireRecord,
  requireString,
  optionalString,
} from '@/shared/lib/contractValidation';
import { omitUndefinedProperties } from '@/shared/lib/object';
import type { AuthUser } from '@/shared/types/auth';

export const parseAuthUser = (value: unknown): AuthUser => {
  const user = requireRecord(value);

  return omitUndefinedProperties({
    id: requireNumber(user.id),
    email: requireString(user.email),
    firstName: requireString(user.firstName),
    lastName: requireString(user.lastName),
    address: requireString(user.address),
    postalCode: requireString(user.postalCode),
    city: requireString(user.city),
    birthDate: requireString(user.birthDate),
    phoneNumber: requireString(user.phoneNumber),
    gender: requireString(user.gender),
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
