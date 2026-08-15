import { useCallback, useState } from 'react';

import type { AttributeRowState, VariantRowState } from '@/features/admin/catalog/utils/productFormConfig';

export const useProductVariantRows = () => {
  const [variantRows, setVariantRows] = useState<VariantRowState[]>([]);

  const addVariantRow = useCallback((attributes: AttributeRowState[] = []) => {
    setVariantRows((previous) => [
      ...previous,
      { attributes, stock: '0', salePrice: '', rentalPrice: '' },
    ]);
  }, []);

  const updateVariantRow = useCallback(
    (index: number, field: keyof VariantRowState, value: string) => {
      setVariantRows((previous) =>
        previous.map((row, rowIndex) => (rowIndex === index ? { ...row, [field]: value } : row)),
      );
    },
    [],
  );

  const addVariantAttributeRow = useCallback((index: number) => {
    setVariantRows((previous) =>
      previous.map((row, rowIndex) =>
        rowIndex === index
          ? { ...row, attributes: [...row.attributes, { code: '', label: '', value: '' }] }
          : row,
      ),
    );
  }, []);

  const updateVariantAttributeRow = useCallback(
    (rowIndex: number, attributeIndex: number, field: keyof AttributeRowState, value: string) => {
      setVariantRows((previous) =>
        previous.map((row, currentRowIndex) =>
          currentRowIndex === rowIndex
            ? {
                ...row,
                attributes: row.attributes.map((attribute, currentAttributeIndex) =>
                  currentAttributeIndex === attributeIndex
                    ? { ...attribute, [field]: value }
                    : attribute,
                ),
              }
            : row,
        ),
      );
    },
    [],
  );

  const removeVariantAttributeRow = useCallback((rowIndex: number, attributeIndex: number) => {
    setVariantRows((previous) =>
      previous.map((row, currentRowIndex) =>
        currentRowIndex === rowIndex
          ? {
              ...row,
              attributes: row.attributes.filter((_, currentAttributeIndex) => currentAttributeIndex !== attributeIndex),
            }
          : row,
      ),
    );
  }, []);

  const removeVariantRow = useCallback((index: number) => {
    setVariantRows((previous) => previous.filter((_, rowIndex) => rowIndex !== index));
  }, []);

  const resetVariantRows = useCallback(() => setVariantRows([]), []);

  return {
    variantRows,
    setVariantRows,
    addVariantRow,
    updateVariantRow,
    removeVariantRow,
    addVariantAttributeRow,
    updateVariantAttributeRow,
    removeVariantAttributeRow,
    resetVariantRows,
  };
};
