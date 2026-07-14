import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';

import { fetchMobileProducts, type MobileCatalogProduct } from '@/src/features/catalog/api';

export default function ProductsScreen() {
  const [query, setQuery] = useState('');
  const [activeSellingType, setActiveSellingType] = useState<'sale' | 'rental' | null>(null);
  const [products, setProducts] = useState<MobileCatalogProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const loadProducts = async (options?: { refresh?: boolean }) => {
    try {
      if (options?.refresh) {
        setRefreshing(true);
      } else {
        setLoading(true);
      }

      setError(null);
      const items = await fetchMobileProducts({
        q: query.trim() || undefined,
        sellingType: activeSellingType ?? undefined,
      });
      setProducts(items);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : 'Impossible de charger le catalogue.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    const timeout = setTimeout(() => {
      void loadProducts();
    }, 250);

    return () => clearTimeout(timeout);
  }, [query, activeSellingType]);

  const resultLabel = useMemo(() => {
    if (loading) return 'Chargement du catalogue…';
    if (products.length === 0) return 'Aucun produit trouvé';
    return `${products.length} produit${products.length > 1 ? 's' : ''}`;
  }, [loading, products.length]);

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <ScrollView
        style={styles.screen}
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => void loadProducts({ refresh: true })} />}>
        <View style={styles.heroCard}>
          <Text style={styles.eyebrow}>Produits</Text>
          <Text style={styles.title}>Catalogue Hociatec</Text>
          <Text style={styles.text}>
            Recherche mobile par mot-clé et filtre rapide vente/location sur les produits publiés du site.
          </Text>
        </View>

        <View style={styles.searchCard}>
          <TextInput
            value={query}
            onChangeText={setQuery}
            placeholder="Rechercher un produit, une marque…"
            placeholderTextColor="#7b8e9d"
            style={styles.searchInput}
            autoCapitalize="none"
            autoCorrect={false}
          />
          <View style={styles.filterRow}>
            <FilterChip
              label="Tous"
              active={activeSellingType === null}
              onPress={() => setActiveSellingType(null)}
            />
            <FilterChip
              label="Vente"
              active={activeSellingType === 'sale'}
              onPress={() => setActiveSellingType('sale')}
            />
            <FilterChip
              label="Location"
              active={activeSellingType === 'rental'}
              onPress={() => setActiveSellingType('rental')}
            />
          </View>
          <Text style={styles.resultLabel}>{resultLabel}</Text>
        </View>

        {loading ? (
          <View style={styles.stateCard}>
            <ActivityIndicator color="#0f7cc0" />
            <Text style={styles.stateText}>Chargement des produits…</Text>
          </View>
        ) : null}

        {error ? (
          <View style={styles.stateCard}>
            <Text style={styles.errorText}>{error}</Text>
            <Pressable style={styles.retryButton} onPress={() => void loadProducts()}>
              <Text style={styles.retryButtonText}>Réessayer</Text>
            </Pressable>
          </View>
        ) : null}

        {!loading && !error && products.length === 0 ? (
          <View style={styles.stateCard}>
            <Text style={styles.stateText}>
              Aucun produit ne correspond à cette recherche pour le moment.
            </Text>
          </View>
        ) : null}

        {!loading && !error && products.length > 0 ? (
          <View style={styles.list}>
            {products.map((product) => (
              <Pressable
                key={product.id}
                style={styles.productCard}
                onPress={() =>
                  router.push({
                    pathname: '/products/[slug]',
                    params: { slug: product.slug },
                  } as any)
                }>
                {product.imageUrl ? (
                  <Image
                    source={{ uri: product.imageUrl }}
                    style={styles.productImage}
                    resizeMode="cover"
                    accessibilityLabel={product.imageAlt || product.name}
                  />
                ) : (
                  <View style={[styles.productImage, styles.productImageFallback]}>
                    <Text style={styles.productImageFallbackText}>{product.name.charAt(0).toUpperCase()}</Text>
                  </View>
                )}
                <View style={styles.productContent}>
                  <Text style={styles.productCategory}>{product.category.name}</Text>
                  <Text style={styles.productName}>{product.name}</Text>
                  <Text style={styles.productMeta}>
                    {product.brand || 'Hociatec'} · {product.sellingType === 'sale' ? 'Vente' : 'Location'}
                  </Text>
                  {product.shortDescription ? (
                    <Text style={styles.productDescription} numberOfLines={3}>
                      {product.shortDescription}
                    </Text>
                  ) : null}
                  <View style={styles.productFooter}>
                    <Text style={styles.productPrice}>
                      {(product.priceCents / 100).toLocaleString('fr-FR', {
                        style: 'currency',
                        currency: 'EUR',
                      })}
                    </Text>
                    <Text style={styles.productStock}>{product.stock > 0 ? 'En stock' : 'Stock limité'}</Text>
                  </View>
                </View>
              </Pressable>
            ))}
          </View>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

const FilterChip = ({
  label,
  active,
  onPress,
}: {
  label: string;
  active: boolean;
  onPress: () => void;
}) => (
  <Pressable
    onPress={onPress}
    style={[styles.filterChip, active ? styles.filterChipActive : null]}>
    <Text style={[styles.filterChipText, active ? styles.filterChipTextActive : null]}>{label}</Text>
  </Pressable>
);

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#eef6fb',
  },
  screen: {
    flex: 1,
    backgroundColor: '#eef6fb',
  },
  content: {
    padding: 20,
    gap: 16,
    paddingBottom: 36,
  },
  heroCard: {
    borderRadius: 28,
    backgroundColor: '#0c2436',
    padding: 24,
    gap: 12,
  },
  eyebrow: {
    color: '#88d6ff',
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 1.8,
    textTransform: 'uppercase',
  },
  title: {
    color: '#ffffff',
    fontSize: 28,
    lineHeight: 32,
    fontWeight: '900',
  },
  text: {
    color: '#d5e7f3',
    fontSize: 15,
    lineHeight: 23,
  },
  searchCard: {
    borderRadius: 24,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#d7e5ef',
    padding: 18,
    gap: 14,
  },
  searchInput: {
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#d7e5ef',
    backgroundColor: '#f8fbfe',
    color: '#102c3f',
    fontSize: 15,
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  filterRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  filterChip: {
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#c8dae6',
    backgroundColor: '#f5f9fc',
    paddingHorizontal: 14,
    paddingVertical: 9,
  },
  filterChipActive: {
    backgroundColor: '#0f7cc0',
    borderColor: '#0f7cc0',
  },
  filterChipText: {
    color: '#355468',
    fontSize: 13,
    fontWeight: '700',
  },
  filterChipTextActive: {
    color: '#ffffff',
  },
  resultLabel: {
    color: '#607a8c',
    fontSize: 13,
    fontWeight: '600',
  },
  stateCard: {
    borderRadius: 24,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#d7e5ef',
    padding: 22,
    minHeight: 120,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  stateText: {
    color: '#607a8c',
    fontSize: 14,
    textAlign: 'center',
  },
  errorText: {
    color: '#b42318',
    fontSize: 14,
    fontWeight: '600',
    textAlign: 'center',
  },
  retryButton: {
    borderRadius: 999,
    backgroundColor: '#0f7cc0',
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  retryButtonText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },
  list: {
    gap: 14,
  },
  productCard: {
    overflow: 'hidden',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#d7e5ef',
    backgroundColor: '#ffffff',
  },
  productImage: {
    width: '100%',
    height: 176,
    backgroundColor: '#d9ebf5',
  },
  productImageFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  productImageFallbackText: {
    color: '#0f7cc0',
    fontSize: 40,
    fontWeight: '900',
  },
  productContent: {
    gap: 8,
    padding: 18,
  },
  productCategory: {
    color: '#0f7cc0',
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
    letterSpacing: 1.4,
  },
  productName: {
    color: '#102c3f',
    fontSize: 20,
    fontWeight: '800',
  },
  productMeta: {
    color: '#5c7382',
    fontSize: 13,
    fontWeight: '600',
  },
  productDescription: {
    color: '#5c7382',
    fontSize: 14,
    lineHeight: 21,
  },
  productFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: 12,
    marginTop: 2,
  },
  productPrice: {
    color: '#102c3f',
    fontSize: 18,
    fontWeight: '800',
  },
  productStock: {
    color: '#0f7cc0',
    fontSize: 13,
    fontWeight: '700',
  },
});
