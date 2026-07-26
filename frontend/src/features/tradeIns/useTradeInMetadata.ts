import { useEffect, useState } from 'react';
import { fetchTradeInMetadata } from './api';
import type { TradeInMetadataDto } from './types';

const emptyMetadata: TradeInMetadataDto = { categories: [], conditions: [], statuses: [] };

export const useTradeInMetadata = () => {
  const [metadata, setMetadata] = useState<TradeInMetadataDto>(emptyMetadata);
  useEffect(() => {
    let cancelled = false;
    void fetchTradeInMetadata().then((value) => { if (!cancelled) setMetadata(value); });
    return () => { cancelled = true; };
  }, []);
  return {
    ...metadata,
    categories: metadata.categories.map(({ value, label }) => [value, label] as [string, string]),
    conditions: metadata.conditions.map(({ value, label }) => [value, label] as [string, string]),
  };
};
