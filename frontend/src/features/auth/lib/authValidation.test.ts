import { describe, expect, it } from 'vitest';

import { parseAuthUser } from './authValidation';

describe('parseAuthUser', () => {
  it('normalizes nullable legacy profile fields to empty strings', () => {
    const user = parseAuthUser({
      id: 1,
      email: 'client@hociatec.fr',
      firstName: 'Client',
      lastName: 'Hociatec',
      address: null,
      postalCode: null,
      city: null,
      birthDate: '1990-01-01',
      phoneNumber: null,
      gender: null,
      roles: ['ROLE_USER'],
    });

    expect(user.address).toBe('');
    expect(user.postalCode).toBe('');
    expect(user.city).toBe('');
    expect(user.phoneNumber).toBe('');
    expect(user.gender).toBe('');
  });
});
