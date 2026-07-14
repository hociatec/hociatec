import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  Linking,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router, useLocalSearchParams } from 'expo-router';

import { WEBSITE_URL } from '@/src/config/app';
import { fetchMobileProduct, type MobileCatalogProduct } from '@/src/features/catalog/api';

const formatPrice = (priceCents: number) =>
  (priceCents / 100).toLocaleString('fr-FR', {
    style: 'currency',
    currency: 'EUR',
  });

export default function ProductDetailScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const [product, setProduct] = useState<MobileCatalogProduct | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!slug) {
      setError('Produit introuvable.');
      setLoading(false);
      return;
    }

    let active = true;

    const load = async () => {
      try {
        setLoading(true);
        setError(null);
        const item = await fetchMobileProduct(slug);
        if (active) {
          setProduct(item);
        }
      } catch (loadError) {
        if (active) {
          setError(loadError instanceof Error ? loadError.message : 'Impossible de charger ce produit.');
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    };

    void load();

    return () => {
      active = false;
    };
  }, [slug]);

  const mainImage = useMemo(() => {
    if (!product) return null;
    if (product.imageUrl) return { url: product.imageUrl, alt: product.imageAlt || product.name };
    const galleryImage = product.gallery?.find((item) => item.isPrimary) || product.gallery?.[0];
    if (galleryImage) return { url: galleryImage.url, alt: galleryImage.alt || product.name };
    return null;
  }, [product]);

  const openWebsiteProduct = async () => {
    if (!product) return;
    await Linking.openURL(`${WEBSITE_URL}/catalogue/produits/${product.slug}`);
  };

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <ScrollView style={styles.screen} contentContainerStyle={styles.content}>
        <Pressable style={styles.backButton} onPress={() => router.back()}>
          <Text style={styles.backButtonText}>← Retour</Text>
        </Pressable>

        {loading ? (
          <View style={styles.stateCard}>
            <ActivityIndicator color="#0f7cc0" />
            <Text style={styles.stateText}>Chargement du produit…</Text>
          </View>
        ) : null}

        {error ? (
          <View style={styles.stateCard}>
            <Text style={styles.errorText}>{error}</Text>
            <Pressable
              style={styles.primaryButton}
              onPress={() =>
                slug
                  ? router.replace({
                      pathname: '/products/[slug]',
                      params: { slug },
                    } as any)
                  : router.back()
              }>
              <Text style={styles.primaryButtonText}>Recharger</Text>
            </Pressable>
          </View>
        ) : null}

        {!loading && !error && product ? (
          <>
            <View style={styles.heroCard}>
              {mainImage ? (
                <Image
                  source={{ uri: mainImage.url }}
                  style={styles.heroImage}
                  resizeMode="cover"
                  accessibilityLabel={mainImage.alt}
                />
              ) : (
                <View style={[styles.heroImage, styles.heroImageFallback]}>
                  <Text style={styles.heroImageFallbackText}>{product.name.charAt(0).toUpperCase()}</Text>
                </View>
              )}
              <View style={styles.heroContent}>
                <Text style={styles.eyebrow}>{product.category.name}</Text>
                <Text style={styles.title}>{product.name}</Text>
                <Text style={styles.meta}>
                  {product.brand || 'Hociatec'} · {product.sellingType === 'sale' ? 'Vente' : 'Location'}
                </Text>
                <Text style={styles.price}>
                  {formatPrice(product.effectivePriceCents ?? product.priceCents)}
                </Text>
                <Text style={styles.stock}>{product.stock > 0 ? 'Disponible' : 'Sur demande'}</Text>
              </View>
            </View>

            <View style={styles.sectionCard}>
              <Text style={styles.sectionTitle}>Description</Text>
              <Text style={styles.sectionText}>
                {product.description || product.shortDescription || 'Aucune description détaillée disponible.'}
              </Text>
            </View>

            <View style={styles.sectionCard}>
              <Text style={styles.sectionTitle}>Caractéristiques</Text>
              <View style={styles.specList}>
                <SpecRow label="Référence" value={product.sku} />
                <SpecRow label="Marque" value={product.brand || 'Hociatec'} />
                <SpecRow label="Capacité" value={product.storageCapacity || 'Non renseignée'} />
                <SpecRow label="Mémoire" value={product.memoryRam || 'Non renseignée'} />
                <SpecRow label="Couleur" value={product.color || 'Non renseignée'} />
              </View>
            </View>

            <View style={styles.actionsCard}>
              <Pressable style={styles.primaryButton} onPress={() => void openWebsiteProduct()}>
                <Text style={styles.primaryButtonText}>Voir sur le site</Text>
              </Pressable>
              <Pressable style={styles.secondaryButton} onPress={() => void Linking.openURL(`${WEBSITE_URL}/contact`)}>
                <Text style={styles.secondaryButtonText}>Contacter Hociatec</Text>
              </Pressable>
            </View>
          </>
        ) : null}
      </ScrollView>
    </SafeAreaView>
  );
}

const SpecRow = ({ label, value }: { label: string; value: string }) => (
  <View style={styles.specRow}>
    <Text style={styles.specLabel}>{label}</Text>
    <Text style={styles.specValue}>{value}</Text>
  </View>
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
    gap: 16,
    padding: 20,
    paddingBottom: 36,
  },
  backButton: {
    alignSelf: 'flex-start',
    borderRadius: 999,
    backgroundColor: '#dff2ff',
    paddingHorizontal: 14,
    paddingVertical: 9,
  },
  backButtonText: {
    color: '#0f7cc0',
    fontSize: 14,
    fontWeight: '700',
  },
  stateCard: {
    minHeight: 180,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#d7e5ef',
    backgroundColor: '#ffffff',
    padding: 24,
  },
  stateText: {
    color: '#5d7382',
    fontSize: 14,
    textAlign: 'center',
  },
  errorText: {
    color: '#b42318',
    fontSize: 14,
    fontWeight: '600',
    textAlign: 'center',
  },
  heroCard: {
    overflow: 'hidden',
    borderRadius: 28,
    borderWidth: 1,
    borderColor: '#d7e5ef',
    backgroundColor: '#ffffff',
  },
  heroImage: {
    width: '100%',
    height: 260,
    backgroundColor: '#d9ebf5',
  },
  heroImageFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroImageFallbackText: {
    color: '#0f7cc0',
    fontSize: 56,
    fontWeight: '900',
  },
  heroContent: {
    gap: 8,
    padding: 20,
  },
  eyebrow: {
    color: '#0f7cc0',
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
    letterSpacing: 1.5,
  },
  title: {
    color: '#102c3f',
    fontSize: 28,
    lineHeight: 33,
    fontWeight: '900',
  },
  meta: {
    color: '#5d7382',
    fontSize: 14,
    fontWeight: '600',
  },
  price: {
    color: '#102c3f',
    fontSize: 24,
    fontWeight: '900',
  },
  stock: {
    color: '#0f7cc0',
    fontSize: 14,
    fontWeight: '700',
  },
  sectionCard: {
    borderRadius: 24,
    borderWidth: 1,
    borderColor: '#d7e5ef',
    backgroundColor: '#ffffff',
    padding: 20,
    gap: 12,
  },
  sectionTitle: {
    color: '#102c3f',
    fontSize: 20,
    fontWeight: '800',
  },
  sectionText: {
    color: '#5d7382',
    fontSize: 15,
    lineHeight: 23,
  },
  specList: {
    gap: 12,
  },
  specRow: {
    gap: 4,
    paddingBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#e8f0f5',
  },
  specLabel: {
    color: '#607a8c',
    fontSize: 12,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 1.2,
  },
  specValue: {
    color: '#102c3f',
    fontSize: 15,
    fontWeight: '600',
  },
  actionsCard: {
    gap: 10,
  },
  primaryButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 18,
    backgroundColor: '#0f7cc0',
    paddingHorizontal: 18,
    paddingVertical: 14,
  },
  primaryButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '800',
  },
  secondaryButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#bdd1df',
    backgroundColor: '#ffffff',
    paddingHorizontal: 18,
    paddingVertical: 14,
  },
  secondaryButtonText: {
    color: '#102c3f',
    fontSize: 14,
    fontWeight: '700',
  },
});
