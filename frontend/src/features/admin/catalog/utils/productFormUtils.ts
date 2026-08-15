import { slugify as localSlugify } from '@/shared/lib/slugify';
import { parseNonNegativeDecimal } from '@/shared/lib/parsers';
import { normalizeSearchText } from '@/shared/lib/searchText';
import type { CatalogCategoryAttributeDefinition } from '@/features/catalog/adminApi';
import type { AttributeRowState, VariantRowState } from './productFormConfig';

export const slugify = (value: string) => localSlugify(value);

export const buildVariantIdentityKey = (attributes: Array<{
  code?: string | null;
  value?: string | null;
}> = []) =>
  normalizeAttributeRows(attributes)
    .map((attribute) => `${attribute.code}|${normalizeTextValue(attribute.value)}`)
    .sort()
    .join('||');

export const normalizeTextValue = (value: string | null | undefined) =>
  normalizeSearchText(value).trim();

export const formatVariantConflictLabel = (
  attributes: Array<{ label?: string | null; value?: string | null }> = [],
) => {
  const parts = attributes
    .map((attribute) => {
      const label = attribute.label?.trim();
      const value = attribute.value?.trim();

      if (!value) {
        return null;
      }

      return label ? `${label}: ${value}` : value;
    })
    .filter((value): value is string => Boolean(value));

  return parts.length > 0 ? parts.join(' / ') : 'cette variante';
};

export const formatVariantDetails = (product: {
  attributes?: Array<{ label?: string | null; value?: string | null }> | null;
  color?: string | null;
  storageCapacity?: string | null;
}) => {
  const details = (product.attributes ?? [])
    .map((attribute) => attribute.value?.trim() || null)
    .filter((value): value is string => Boolean(value));

  if (details.length > 0) {
    return details.join(' • ');
  }

  const legacyDetails = [product.color, product.storageCapacity].filter((value): value is string =>
    Boolean(value && value.trim() !== ''),
  );

  return legacyDetails.length > 0 ? legacyDetails.join(' • ') : 'Aucune précision';
};

export const parseProductPrice = (value: string) => {
  return parseNonNegativeDecimal(value, Number.NaN);
};

export const normalizeAttributeCode = (value: string) =>
  value
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

export const normalizeAttributeRows = (
  attributes: Array<{ code?: string | null; label?: string | null; value?: string | null }> = [],
): AttributeRowState[] => {
  const normalized = new Map<string, AttributeRowState>();

  attributes.forEach((attribute) => {
    const label = attribute.label?.trim() ?? '';
    const value = attribute.value?.trim() ?? '';
    const rawCode = attribute.code?.trim() ?? label;
    const code = normalizeAttributeCode(rawCode);

    if (!code || !label || !value) {
      return;
    }

    normalized.set(code, { code, label, value });
  });

  return Array.from(normalized.values());
};

export const applyCategoryAttributeDefinitions = (
  attributes: Array<{ code?: string | null; label?: string | null; value?: string | null }> = [],
  definitions: CatalogCategoryAttributeDefinition[] = [],
): AttributeRowState[] => {
  const normalizedExisting = normalizeAttributeRows(attributes);
  const existingByCode = new Map(normalizedExisting.map((attribute) => [attribute.code, attribute]));
  const configuredCodes = new Set<string>();
  const configured = definitions
    .map((definition) => {
      const code = normalizeAttributeCode(definition.code || definition.label);
      const label = definition.label.trim();

      if (!code || !label) {
        return null;
      }

      configuredCodes.add(code);
      const current = existingByCode.get(code);

      return {
        code,
        label,
        value: current?.value ?? '',
      };
    })
    .filter((attribute): attribute is AttributeRowState => Boolean(attribute));
  const extras = normalizedExisting.filter((attribute) => !configuredCodes.has(attribute.code));

  return [...configured, ...extras];
};

export const buildAttributesFromDefinitions = (
  definitions: CatalogCategoryAttributeDefinition[] = [],
): AttributeRowState[] =>
  definitions
    .map((definition) => {
      const code = normalizeAttributeCode(definition.code || definition.label);
      const label = definition.label.trim();

      if (!code || !label) {
        return null;
      }

      return {
        code,
        label,
        value: '',
      };
    })
    .filter((attribute): attribute is AttributeRowState => Boolean(attribute));

export const validateRequiredAttributes = (
  attributes: Array<{ code?: string | null; value?: string | null }> = [],
  definitions: CatalogCategoryAttributeDefinition[] = [],
) => {
  const normalized = new Map(
    normalizeAttributeRows(attributes).map((attribute) => [attribute.code, attribute.value.trim()]),
  );

  for (const definition of definitions) {
    if (!definition.isRequired) {
      continue;
    }

    const code = normalizeAttributeCode(definition.code || definition.label);
    const label = definition.label.trim() || definition.code.trim();

    if (!code) {
      continue;
    }

    if (!normalized.get(code)) {
      return `${label} est obligatoire pour cette catégorie.`;
    }
  }

  return null;
};

export const validateAttributeValuesAgainstDefinitions = (
  attributes: Array<{ code?: string | null; value?: string | null }> = [],
  definitions: CatalogCategoryAttributeDefinition[] = [],
) => {
  const definitionsByCode = new Map(
    definitions.map((definition) => [normalizeAttributeCode(definition.code || definition.label), definition] as const),
  );

  for (const attribute of normalizeAttributeRows(attributes)) {
    const definition = definitionsByCode.get(attribute.code);
    if (!definition) {
      continue;
    }

    const value = attribute.value.trim();
    if (value === '') {
      continue;
    }

    if (definition.inputType === 'number' && Number.isNaN(Number(value))) {
      return `${definition.label} doit être un nombre valide.`;
    }

    if (definition.inputType === 'boolean' && !['oui', 'non', 'true', 'false', '1', '0'].includes(normalizeTextValue(value))) {
      return `${definition.label} doit valoir Oui ou Non.`;
    }

    if (definition.inputType === 'color' && !/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/iu.test(value)) {
      return `${definition.label} doit être une couleur hexadécimale valide.`;
    }

    if (definition.inputType === 'select') {
      const allowedOptions = new Set((definition.options ?? []).map((option) => normalizeTextValue(option)));
      if (allowedOptions.size > 0 && !allowedOptions.has(normalizeTextValue(value))) {
        return `${definition.label} doit correspondre à une option autorisée.`;
      }
    }
  }

  return null;
};

export const applyCategorySchemaToVariantRows = (
  rows: VariantRowState[],
  definitions: CatalogCategoryAttributeDefinition[] = [],
) =>
  rows.map((row) => ({
    ...row,
    attributes: applyCategoryAttributeDefinitions(row.attributes, definitions),
  }));
