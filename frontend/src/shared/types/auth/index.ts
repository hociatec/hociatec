export interface AuthUser {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  address: string;
  postalCode: string;
  city: string;
  birthDate: string;
  phoneNumber: string;
  gender: string;
  roles: string[];
}

export interface AuthTokens {
  token: string;
  refreshToken?: string;
}
