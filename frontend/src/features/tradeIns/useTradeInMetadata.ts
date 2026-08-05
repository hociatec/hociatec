import { useQuery } from '@tanstack/react-query';

import { tradeInQueryKeys } from '@/shared/lib/queryKeys';
import { fetchTradeInMetadata } from './api';
import type { TradeInMetadataDto } from './types';

const emptyMetadata: TradeInMetadataDto = { categories: [], conditions: [], statuses: [], paymentMethods: [], paymentStatuses: [] };

export const useTradeInMetadata = () => {
  const metadataQuery = useQuery<TradeInMetadataDto, Error>({
    queryKey: tradeInQueryKeys.metadata(),
    queryFn: fetchTradeInMetadata,
  });
  const metadata = metadataQuery.data ?? emptyMetadata;

  return {
    ...metadata,
    categories: metadata.categories.map(({ value, label }) => [value, label] as [string, string]),
    conditions: metadata.conditions.map(({ value, label }) => [value, label] as [string, string]),
  };
};
