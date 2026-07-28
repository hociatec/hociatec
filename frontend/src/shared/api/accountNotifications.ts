import { httpClient } from '@/shared/lib/httpClient';
import { isApiOk, type ApiResponse } from '@/shared/types/api';

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

  if (isApiOk(data)) {
    return data.data.items;
  }

  throw new Error(
    data.status === 'error' ? data.message : 'Impossible de charger les notifications',
  );
};

export const fetchAccountNotificationsReadState =
  async (): Promise<AccountNotificationsReadStateDto> => {
    const { data } = await httpClient.get<
      ApiResponse<{ readState: AccountNotificationsReadStateDto }>
    >('/api/account-notifications/me/read-state');

    if (isApiOk(data)) {
      return data.data.readState;
    }

    throw new Error(
      data.status === 'error' ? data.message : 'Impossible de charger les notifications lues',
    );
  };

export const updateAccountNotificationsReadState = async (
  seenSignature: string,
): Promise<AccountNotificationsReadStateDto> => {
  const { data } = await httpClient.patch<
    ApiResponse<{ readState: AccountNotificationsReadStateDto }>
  >('/api/account-notifications/me/read-state', { seenSignature });

  if (isApiOk(data)) {
    return data.data.readState;
  }

  throw new Error(
    data.status === 'error' ? data.message : 'Impossible de marquer les notifications comme lues',
  );
};

export const markAccountNotificationsSeen = async (
  seenKeys: string[],
): Promise<AccountNotificationsReadStateDto> => {
  const { data } = await httpClient.patch<
    ApiResponse<{ readState: AccountNotificationsReadStateDto }>
  >('/api/account-notifications/me/read-state', { seenKeys });

  if (isApiOk(data)) {
    return data.data.readState;
  }

  throw new Error(
    data.status === 'error' ? data.message : 'Impossible de marquer les notifications comme lues',
  );
};

export const dismissAccountNotification = async (
  dismissedKey: string,
): Promise<AccountNotificationsReadStateDto> => {
  const { data } = await httpClient.patch<
    ApiResponse<{ readState: AccountNotificationsReadStateDto }>
  >('/api/account-notifications/me/read-state', { dismissedKey });

  if (isApiOk(data)) {
    return data.data.readState;
  }

  throw new Error(
    data.status === 'error' ? data.message : 'Impossible de supprimer la notification',
  );
};
