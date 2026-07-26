import type { ChangeEvent, Dispatch, SetStateAction } from 'react';

import type { ProductFormState } from '@/features/admin/catalog/utils/productFormConfig';
import { slugify } from '@/features/admin/catalog/utils/productFormUtils';

export const useProductFormFields = (
  setForm: Dispatch<SetStateAction<ProductFormState>>,
) => {
  const handleFieldChange = (name: keyof ProductFormState, value: string) => {
    setForm((previous) => {
      if (name === 'name') {
        const generatedSlug = slugify(value);
        const shouldSyncSlug = previous.slug.trim() === '' || previous.slug === slugify(previous.name);
        return { ...previous, name: value, slug: shouldSyncSlug ? generatedSlug : previous.slug };
      }
      if (name === 'slug') return { ...previous, slug: slugify(value) };
      return { ...previous, [name]: value };
    });
  };

  const handleChange = (
    event: ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>,
  ) => {
    const { name, value, type, checked } = event.target as HTMLInputElement;
    if (type === 'checkbox') {
      setForm((previous) => ({ ...previous, [name]: checked }));
      return;
    }
    handleFieldChange(name as keyof ProductFormState, value);
  };

  return { handleFieldChange, handleChange };
};
