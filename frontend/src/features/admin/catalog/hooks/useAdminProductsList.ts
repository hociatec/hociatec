import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';

import {
  deleteProduct,
  fetchAdminCategories,
  fetchAdminProductsPage,
} from '@/features/catalog/adminApi';
import { groupCatalogProducts } from '@/features/catalog/adminApi';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';
import { adminCatalogQueryKeys } from '@/features/admin/catalog/queryKeys';
import { omitUndefinedProperties } from '@/shared/lib/object';

const PRODUCTS_PER_PAGE = 10;
const parseNumber = (value: string | null) => {
  if (null === value || '' === value) return null;
  const parsed = Number(value);

  return Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
};

export const useAdminProductsList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const confirm = useConfirm();
  const queryClient = useQueryClient();
  const [message, setMessage] = useState<string | null>(null);
  const [searchValue, setSearchValue] = useState(searchParams.get('search') ?? '');
  const [categoryValue, setCategoryValue] = useState(searchParams.get('category') ?? 'all');
  const [stockValue, setStockValue] = useState<'all' | 'low'>(
    'low' === searchParams.get('stock') ? 'low' : 'all',
  );
  const [featuredValue, setFeaturedValue] = useState<'all' | 'featured'>(
    '1' === searchParams.get('featured') ? 'featured' : 'all',
  );
  const [sellingTypeValue, setSellingTypeValue] = useState<'all' | 'sale' | 'rental'>(
    (searchParams.get('sellingType') as 'all' | 'sale' | 'rental' | null) ?? 'all',
  );
  const [minPriceValue, setMinPriceValue] = useState<number | null>(
    parseNumber(searchParams.get('minPrice')),
  );
  const [maxPriceValue, setMaxPriceValue] = useState<number | null>(
    parseNumber(searchParams.get('maxPrice')),
  );
  const [sortValue, setSortValue] = useState(
    searchParams.get('sort') ?? 'created_desc',
  );
  const [page, setPageValue] = useState(Math.max(1, Number(searchParams.get('page') ?? 1)));

  const productParams = useMemo(
    () => omitUndefinedProperties({
      page,
      perPage: PRODUCTS_PER_PAGE,
      search: searchValue.trim() || undefined,
      category: 'all' === categoryValue ? undefined : categoryValue,
      featured: 'featured' === featuredValue ? true : undefined,
      sellingType: 'all' === sellingTypeValue ? undefined : sellingTypeValue,
      minPrice: minPriceValue ?? undefined,
      maxPrice: maxPriceValue ?? undefined,
      stock: 'low' === stockValue ? ('low' as const) : undefined,
      sort: sortValue as Parameters<typeof fetchAdminProductsPage>[0]['sort'],
    }),
    [
      page,
      searchValue,
      categoryValue,
      featuredValue,
      sellingTypeValue,
      minPriceValue,
      maxPriceValue,
      stockValue,
      sortValue,
    ],
  );
  const categoriesQuery = useQuery({
    queryKey: adminCatalogQueryKeys.categories(),
    queryFn: fetchAdminCategories,
  });
  const productsQuery = useQuery({
    queryKey: adminCatalogQueryKeys.productsPage(productParams),
    queryFn: () => fetchAdminProductsPage(productParams),
  });
  const deleteMutation = useMutation({
    mutationFn: deleteProduct,
    onSuccess: (response) => {
      void queryClient.invalidateQueries({ queryKey: adminCatalogQueryKeys.products() });
      setMessage(response.message ?? 'Le produit a bien été supprimé du catalogue.');
    },
  });
  const products = productsQuery.data?.items ?? [];
  const meta = productsQuery.data?.meta ?? null;
  const categories = categoriesQuery.data ?? [];
  const error =
    productsQuery.error
      ? getHttpErrorMessage(productsQuery.error, "Le catalogue admin n'a pas pu être chargé.")
      : categoriesQuery.error
        ? getHttpErrorMessage(categoriesQuery.error, "Les catégories n'ont pas pu être chargées.")
        : deleteMutation.error
          ? getHttpErrorMessage(deleteMutation.error, "Le produit n'a pas pu être supprimé.")
          : null;

  useEffect(() => {
    const next = new URLSearchParams();
    if (page > 1) next.set('page', String(page));
    if (searchValue.trim()) next.set('search', searchValue.trim());
    if ('all' !== categoryValue) next.set('category', categoryValue);
    if ('all' !== stockValue) next.set('stock', stockValue);
    if ('all' !== featuredValue) next.set('featured', '1');
    if ('all' !== sellingTypeValue) next.set('sellingType', sellingTypeValue);
    if (null !== minPriceValue) next.set('minPrice', String(minPriceValue));
    if (null !== maxPriceValue) next.set('maxPrice', String(maxPriceValue));
    if ('created_desc' !== sortValue) next.set('sort', sortValue);
    setSearchParams(next, { replace: true });
  }, [
    page,
    searchValue,
    categoryValue,
    stockValue,
    featuredValue,
    sellingTypeValue,
    minPriceValue,
    maxPriceValue,
    sortValue,
    setSearchParams,
  ]);

  const filteredProducts = useMemo(() => groupCatalogProducts(products), [products]);
  const hasActiveFilters =
    searchValue.trim() !== '' ||
    categoryValue !== 'all' ||
    stockValue !== 'all' ||
    featuredValue !== 'all' ||
    sellingTypeValue !== 'all' ||
    null !== minPriceValue ||
    null !== maxPriceValue ||
    sortValue !== 'created_desc';

  const resetFilters = () => {
    setPageValue(1);
    setSearchValue('');
    setCategoryValue('all');
    setStockValue('all');
    setFeaturedValue('all');
    setSellingTypeValue('all');
    setMinPriceValue(null);
    setMaxPriceValue(null);
    setSortValue('created_desc');
  };

  const updateSearch = (value: string) => {
    setPageValue(1);
    setSearchValue(value);
  };
  const updateCategory = (value: string) => {
    setPageValue(1);
    setCategoryValue(value);
  };
  const updateStock = (value: 'all' | 'low') => {
    setPageValue(1);
    setStockValue(value);
  };
  const updateFeatured = (value: 'all' | 'featured') => {
    setPageValue(1);
    setFeaturedValue(value);
  };
  const updateSellingType = (value: 'all' | 'sale' | 'rental') => {
    setPageValue(1);
    setSellingTypeValue(value);
  };
  const updatePrice = (next: { min: number | null; max: number | null }) => {
    setPageValue(1);
    setMinPriceValue(next.min);
    setMaxPriceValue(next.max);
  };
  const updateSort = (value: string) => {
    setPageValue(1);
    setSortValue(value);
  };

  const handleDelete = async (productId: number) => {
    const product = products.find((item) => item.id === productId);
    if (
      !(await confirm({
        title: 'Supprimer le produit',
        description: `Supprimer ${product ? `"${product.name}"` : 'ce produit'} du catalogue ?`,
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler',
      }))
    )
      return;
    setMessage(null);
    deleteMutation.mutate(productId);
  };

  return {
    categories,
    loading: productsQuery.isLoading || categoriesQuery.isLoading,
    error,
    message,
    search: searchValue,
    setSearch: updateSearch,
    filterCategory: categoryValue,
    setFilterCategory: updateCategory,
    stockFilter: stockValue,
    setStockFilter: updateStock,
    featuredFilter: featuredValue,
    setFeaturedFilter: updateFeatured,
    sellingTypeFilter: sellingTypeValue,
    setSellingTypeFilter: updateSellingType,
    minPrice: minPriceValue,
    maxPrice: maxPriceValue,
    setPriceRange: updatePrice,
    sort: sortValue,
    setSort: updateSort,
    filteredProducts,
    hasActiveFilters,
    page,
    setPage: setPageValue,
    meta,
    resetFilters,
    handleDelete,
  };
};
