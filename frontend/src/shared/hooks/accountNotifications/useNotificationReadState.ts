import { useMemo } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import type { Dispatch, SetStateAction } from 'react';

import {
  fetchAccountNotificationsReadState,
  type AccountNotificationsReadStateDto,
} from '@/shared/api/accountNotifications';
import { accountQueryKeys } from '@/features/account/queryKeys';
import { emptyReadState } from './constants';

export const useNotificationReadState = () => {
  const queryClient = useQueryClient();
  const readStateQuery = useQuery<AccountNotificationsReadStateDto, Error>({
    queryKey: accountQueryKeys.notificationsReadState(),
    queryFn: fetchAccountNotificationsReadState,
  });
  const readState = readStateQuery.data ?? emptyReadState;
  const setReadState: Dispatch<SetStateAction<AccountNotificationsReadStateDto>> = (nextState) => {
    queryClient.setQueryData<AccountNotificationsReadStateDto>(
      accountQueryKeys.notificationsReadState(),
      (current = emptyReadState) =>
        typeof nextState === 'function' ? nextState(current) : nextState,
    );
  };

  return {
    dismissedKeys: useMemo(() => new Set(readState.dismissedKeys), [readState.dismissedKeys]),
    readState,
    readStateLoading: readStateQuery.isLoading,
    seenKeys: useMemo(() => new Set(readState.seenKeys), [readState.seenKeys]),
    setReadState,
  };
};
