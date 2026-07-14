import { API_BASE_URL } from '@/src/config/app';

export interface MobileCatalogProduct {
  id: number;
  name: string;
  slug: string;
  sku: string;
  shortDescription: string | null;
  description?: string;
  priceCents: number;
  effectivePriceCents?: number;
  sellingType: 'sale' | 'rental';
  brand?: string | null;
  storageCapacity?: string | null;
  memoryRam?: string | null;
  color?: string | null;
  stock: number;
  imageUrl: string | null;
  imageAlt: string | null;
  gallery?: Array<{
    position: number;
    url: string;
    alt: string;
    isPrimary: boolean;
  }>;
  category: {
    id: number;
    name: string;
    slug: string;
  };
}

interface ApiSuccess<T> {
  status: 'success';
  data: T;
}

interface ApiError {
  status: 'error';
  message: string;
}

type ApiResponse<T> = ApiSuccess<T> | ApiError;

const buildQuery = (params: Record<string, string | undefined>) => {
  const searchParams = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value && value.trim() !== '') {
      searchParams.set(key, value);
    }
  });

  const query = searchParams.toString();
  return query ? `?${query}` : '';
};

const readJson = async <T>(path: string) => {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: {
      Accept: 'application/json',
    },
  });

  if (!response.ok) {
    throw new Error(`Erreur API (${response.status})`);
  }

  const payload = (await response.json()) as ApiResponse<T>;

  if (payload.status !== 'success') {
    throw new Error(payload.message || 'Réponse API invalide.');
  }

  return payload.data;
};

export const fetchHomepageProducts = async () => {
  const data = await readJson<{ items: MobileCatalogProduct[] }>(
    `/api/public/catalog/products${buildQuery({ homepage: '1' })}`,
  );

  return data.items;
};

export const fetchMobileProducts = async (params: {
  q?: string;
  sellingType?: 'sale' | 'rental';
}) => {
  const data = await readJson<{ items: MobileCatalogProduct[] }>(
    `/api/public/catalog/products${buildQuery({
      q: params.q,
      sellingType: params.sellingType,
    })}`,
  );

  return data.items;
};

export const fetchMobileProduct = async (slug: string) => {
  return readJson<MobileCatalogProduct>(`/api/public/catalog/products/${slug}`);
};
