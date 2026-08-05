import { useMemo, useState, type Dispatch, type SetStateAction } from 'react';

import type { CatalogBrand } from '@/features/catalog/adminApi';
import type { ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';

export const useProductBrandSelection = (
  brands: CatalogBrand[],
  form: ProductFormState,
  setForm: Dispatch<SetStateAction<ProductFormState>>,
) => {
  const [brandQuery, setBrandQuery] = useState('');
  const filteredBrands = useMemo(() => {
    const search = brandQuery.trim().toLowerCase();
    if (search === '') {
      return form.brand
        ? brands.filter((brand) => brand.name.toLowerCase() === form.brand.trim().toLowerCase())
        : [];
    }
    return brands.filter((brand) => brand.name.toLowerCase().includes(search)).slice(0, 8);
  }, [brandQuery, brands, form.brand]);

  const handleBrandQueryChange = (value: string) => {
    setBrandQuery(value);
    setForm((previous) => {
      const selectedBrand = previous.brand.trim() === ''
        ? null
        : brands.find((brand) => brand.name.toLowerCase() === previous.brand.trim().toLowerCase()) ?? null;
      if (selectedBrand && selectedBrand.name.toLowerCase() === value.trim().toLowerCase()) return previous;
      return { ...previous, brand: '' };
    });
  };

  const handleBrandSelection = (brand: CatalogBrand) => {
    setBrandQuery(brand.name);
    setForm((previous) => ({ ...previous, brand: brand.name }));
  };

  return { brandQuery, setBrandQuery, filteredBrands, handleBrandQueryChange, handleBrandSelection };
};
