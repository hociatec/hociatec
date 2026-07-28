import { useEffect, useMemo, useState } from 'react';

import {
  fetchAccountNotificationsReadState,
  type AccountNotificationsReadStateDto,
} from '@/shared/api/accountNotifications';
import { emptyReadState } from './constants';

export const useNotificationReadState = () => {
  const [readState, setReadState] = useState<AccountNotificationsReadStateDto>(emptyReadState);
  const [readStateLoading, setReadStateLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    setReadStateLoading(true);

    void fetchAccountNotificationsReadState()
      .then((nextReadState) => {
        if (!cancelled) setReadState(nextReadState);
      })
      .catch(() => {
        if (!cancelled) setReadState(emptyReadState);
      })
      .finally(() => {
        if (!cancelled) setReadStateLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  return {
    dismissedKeys: useMemo(() => new Set(readState.dismissedKeys), [readState.dismissedKeys]),
    readState,
    readStateLoading,
    seenKeys: useMemo(() => new Set(readState.seenKeys), [readState.seenKeys]),
    setReadState,
  };
};
