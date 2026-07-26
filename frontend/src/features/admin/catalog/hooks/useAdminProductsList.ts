import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  deleteProduct,
  fetchAdminCategories,
  fetchAdminProducts,
  type CatalogCategory,
  type CatalogProduct,
} from '@/features/catalog/api';
import { groupCatalogProducts } from '@/features/catalog/utils/groupProducts';
import { getHttpErrorMessage } from '@/shared/lib/httpClient';
import { useConfirm } from '@/shared/components/ui/confirm';

export const useAdminProductsList = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const confirm = useConfirm();
  const [products, setProducts] = useState<CatalogProduct[]>([]);
  const [categories, setCategories] = useState<CatalogCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [search, setSearch] = useState(searchParams.get('search') ?? '');
  const [filterCategory, setFilterCategory] = useState(searchParams.get('category') ?? 'all');
  const [stockFilter, setStockFilter] = useState<'all' | 'low'>(
    (searchParams.get('stock') as 'all' | 'low' | null) ?? 'all',
  );
  useEffect(() => {
    setLoading(true);
    void Promise.all([fetchAdminProducts(), fetchAdminCategories()])
      .then(([items, categoryItems]) => {
        setProducts(items);
        setCategories(categoryItems);
      })
      .catch((e) => setError(getHttpErrorMessage(e, "Le catalogue admin n'a pas pu être chargé.")))
      .finally(() => setLoading(false));
  }, []);
  const filteredProducts = useMemo(() => {
    const term = search.trim().toLowerCase();
    const slug = filterCategory === 'all' ? null : filterCategory;
    return groupCatalogProducts(products).filter(
      (product) =>
        (!term ||
          [product.name, product.slug, product.sku, product.brand]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(term)) &&
        (!slug || product.category.slug === slug) &&
        (stockFilter !== 'low' || (product.totalStock ?? product.stock) <= 3),
    );
  }, [products, search, filterCategory, stockFilter]);
  useEffect(() => {
    const next = new URLSearchParams();
    if (search.trim()) next.set('search', search.trim());
    if (filterCategory !== 'all') next.set('category', filterCategory);
    if (stockFilter !== 'all') next.set('stock', stockFilter);
    setSearchParams(next, { replace: true });
  }, [search, filterCategory, stockFilter, setSearchParams]);
  const resetFilters = () => {
    setSearch('');
    setFilterCategory('all');
    setStockFilter('all');
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
    setError(null);
    setMessage(null);
    try {
      await deleteProduct(productId);
      setProducts((items) => items.filter((item) => item.id !== productId));
      setMessage('Produit supprimé du catalogue.');
    } catch (e) {
      setError(getHttpErrorMessage(e, "Le produit n'a pas pu être supprimé."));
    }
  };
  return {
    products,
    categories,
    loading,
    error,
    message,
    search,
    setSearch,
    filterCategory,
    setFilterCategory,
    stockFilter,
    setStockFilter,
    filteredProducts,
    resetFilters,
    handleDelete,
  };
};
