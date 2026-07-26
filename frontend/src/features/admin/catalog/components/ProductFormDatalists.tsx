import { DEFAULT_COLOR_OPTIONS, DEFAULT_STORAGE_OPTIONS } from '@/features/admin/catalog/utils/productFormConfig';

export const ProductFormDatalists = () => (
  <>
    <datalist id="storage-capacities">
      {DEFAULT_STORAGE_OPTIONS.map((option) => (
        <option key={option} value={option} />
      ))}
    </datalist>
    <datalist id="color-options">
      {DEFAULT_COLOR_OPTIONS.map((option) => (
        <option key={option} value={option} />
      ))}
    </datalist>
  </>
);
