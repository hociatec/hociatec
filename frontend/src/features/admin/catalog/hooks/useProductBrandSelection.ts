import { useMemo, useState, type Dispatch, type SetStateAction } from 'react';

import type { CatalogBrand } from '@/features/catalog/adminApi';
import type { ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';
import { normalizeSearchText } from '@/shared/lib/searchText';

export const useProductBrandSelection = (
  brands: CatalogBrand[],
  form: ProductFormState,
  setForm: Dispatch<SetStateAction<ProductFormState>>,
) => {
  const [brandQuery, setBrandQuery] = useState('');
  const filteredBrands = useMemo(() => {
    const search = normalizeSearchText(brandQuery);
    if (search === '') {
      const selectedBrandName = normalizeSearchText(form.brand);
      return form.brand
        ? brands.filter((brand) => normalizeSearchText(brand.name) === selectedBrandName)
        : [];
    }
    return brands.filter((brand) => normalizeSearchText(brand.name).includes(search)).slice(0, 8);
  }, [brandQuery, brands, form.brand]);

  const handleBrandQueryChange = (value: string) => {
    setBrandQuery(value);
    setForm((previous) => {
      const selectedBrand = previous.brand.trim() === ''
        ? null
        : brands.find(
            (brand) =>
              normalizeSearchText(brand.name) === normalizeSearchText(previous.brand) &&
              normalizeSearchText(brand.name) === normalizeSearchText(value),
          ) ?? null;
      if (selectedBrand) return previous;
      return { ...previous, brand: '' };
    });
  };

  const handleBrandSelection = (brand: CatalogBrand) => {
    setBrandQuery(brand.name);
    setForm((previous) => ({ ...previous, brand: brand.name }));
  };

  return { brandQuery, setBrandQuery, filteredBrands, handleBrandQueryChange, handleBrandSelection };
};
