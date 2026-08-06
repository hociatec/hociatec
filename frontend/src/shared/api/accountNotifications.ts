import { httpClient } from '@/shared/lib/httpClient';
import { unwrapApiData } from '@/shared/lib/responseHelpers';
import type { ApiResponse } from '@/shared/types/api';

export interface AccountNotificationsReadStateDto {
  seenSignature: string;
  seenKeys: string[];
  dismissedKeys: string[];
}

export interface StoredAccountNotificationDto {
  key: string;
  label: string;
  message: string;
  to: string;
  type: string;
  createdAt: string;
}

export const fetchAccountNotifications = async (): Promise<StoredAccountNotificationDto[]> => {
  const { data } = await httpClient.get<ApiResponse<{ items: StoredAccountNotificationDto[] }>>(
    '/api/account-notifications/me',
  );

  return unwrapApiData(data, 'Impossible de charger les notifications').items;
};

export const fetchAccountNotificationsReadState =
  async (): Promise<AccountNotificationsReadStateDto> => {
    const { data } = await httpClient.get<
      ApiResponse<{ readState: AccountNotificationsReadStateDto }>
    >('/api/account-notifications/me/read-state');

    return unwrapApiData(data, 'Impossible de charger les notifications lues').readState;
  };

export const markAccountNotificationsSeen = async (
  seenKeys: string[],
): Promise<AccountNotificationsReadStateDto> => {
  const { data } = await httpClient.patch<
    ApiResponse<{ readState: AccountNotificationsReadStateDto }>
  >('/api/account-notifications/me/read-state', { seenKeys });

  return unwrapApiData(data, 'Impossible de marquer les notifications comme lues').readState;
};

export const dismissAccountNotification = async (
  dismissedKey: string,
): Promise<AccountNotificationsReadStateDto> => {
  const { data } = await httpClient.patch<
    ApiResponse<{ readState: AccountNotificationsReadStateDto }>
  >('/api/account-notifications/me/read-state', { dismissedKey });

  return unwrapApiData(data, 'Impossible de supprimer la notification').readState;
};

export const dismissAccountNotifications = async (
  dismissedKeys: string[],
): Promise<AccountNotificationsReadStateDto> => {
  const { data } = await httpClient.patch<
    ApiResponse<{ readState: AccountNotificationsReadStateDto }>
  >('/api/account-notifications/me/read-state', { dismissedKeys });

  return unwrapApiData(data, 'Impossible de supprimer les notifications').readState;
};
