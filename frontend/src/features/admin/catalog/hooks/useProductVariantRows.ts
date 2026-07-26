import { useCallback, useState } from 'react';

import type { VariantRowState } from '@/features/admin/catalog/utils/productFormConfig';

export const useProductVariantRows = () => {
  const [variantRows, setVariantRows] = useState<VariantRowState[]>([]);

  const addVariantRow = useCallback(() => {
    setVariantRows((previous) => [...previous, { color: '', storageCapacity: '', stock: '0' }]);
  }, []);

  const updateVariantRow = useCallback(
    (index: number, field: keyof VariantRowState, value: string) => {
      setVariantRows((previous) =>
        previous.map((row, rowIndex) => (rowIndex === index ? { ...row, [field]: value } : row)),
      );
    },
    [],
  );

  const removeVariantRow = useCallback((index: number) => {
    setVariantRows((previous) => previous.filter((_, rowIndex) => rowIndex !== index));
  }, []);

  const resetVariantRows = useCallback(() => setVariantRows([]), []);

  return { variantRows, addVariantRow, updateVariantRow, removeVariantRow, resetVariantRows };
};
