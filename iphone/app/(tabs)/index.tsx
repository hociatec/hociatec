import { useEffect, useState } from 'react';
import { ActivityIndicator, Image, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { router } from 'expo-router';

import { APP_SECTIONS } from '@/src/config/app';
import { fetchHomepageProducts, type MobileCatalogProduct } from '@/src/features/catalog/api';

export default function HomeScreen() {
  const [products, setProducts] = useState<MobileCatalogProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    const load = async () => {
      try {
        setLoading(true);
        setError(null);
        const items = await fetchHomepageProducts();
        if (active) {
          setProducts(items.slice(0, 3));
        }
      } catch (loadError) {
        if (active) {
          setError(loadError instanceof Error ? loadError.message : 'Impossible de charger les produits.');
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
  }, []);

  return (
    <SafeAreaView style={styles.safeArea} edges={['top']}>
      <ScrollView style={styles.screen} contentContainerStyle={styles.content}>
        <View style={styles.heroCard}>
          <Text style={styles.heroEyebrow}>Hociatec</Text>
          <Text style={styles.heroTitle}>Le numérique à taille humaine</Text>
          <Text style={styles.heroText}>
            Vente, location, formation, conception et accompagnement technique.
            Une expérience mobile pensée pour accéder rapidement à l’univers Hociatec.
          </Text>

          <View style={styles.heroPills}>
            <View style={styles.heroPill}>
              <Text style={styles.heroPillText}>Accessible</Text>
            </View>
            <View style={styles.heroPill}>
              <Text style={styles.heroPillText}>Durable</Text>
            </View>
            <View style={styles.heroPill}>
              <Text style={styles.heroPillText}>Concret</Text>
            </View>
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Des solutions concrètes</Text>
          <Text style={styles.sectionText}>
            L’application iPhone s’appuie maintenant sur les données réelles de Hociatec pour préparer une
            vraie version native exploitable.
          </Text>
        </View>

        <View style={styles.cardGrid}>
          {APP_SECTIONS.map((item) => (
            <View key={item.title} style={styles.infoCard}>
              <Text style={styles.infoCardTitle}>{item.title}</Text>
              <Text style={styles.infoCardText}>{item.text}</Text>
            </View>
          ))}
        </View>

        <View style={styles.metricsCard}>
          <Text style={styles.metricsTitle}>Base mobile Hociatec</Text>
          <View style={styles.metricsRow}>
            {[
              { value: products.length > 0 ? String(products.length) : '0', label: 'produits mis en avant chargés' },
              { value: '4', label: 'sections-clés prêtes pour l’app native' },
              { value: 'API', label: error ? 'connexion à vérifier' : 'connexion active ou testée' },
            ].map((item) => (
              <View key={item.label} style={styles.metricItem}>
                <Text style={styles.metricValue}>{item.value}</Text>
                <Text style={styles.metricLabel}>{item.label}</Text>
              </View>
            ))}
          </View>
        </View>

        <View style={styles.catalogSection}>
          <Text style={styles.catalogTitle}>Sélection du moment</Text>
          {loading ? (
            <View style={styles.catalogState}>
              <ActivityIndicator color="#0f7cc0" />
              <Text style={styles.catalogStateText}>Chargement des produits Hociatec…</Text>
            </View>
          ) : null}
          {error ? (
            <View style={styles.catalogState}>
              <Text style={styles.catalogErrorText}>{error}</Text>
            </View>
          ) : null}
          {!loading && !error && products.length === 0 ? (
            <View style={styles.catalogState}>
              <Text style={styles.catalogStateText}>Aucun produit mis en avant disponible pour le moment.</Text>
            </View>
          ) : null}
          {!loading && !error && products.length > 0 ? (
            <View style={styles.catalogList}>
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
                    <Text style={styles.productBrand}>{product.brand || product.category.name}</Text>
                    <Text style={styles.productName}>{product.name}</Text>
                    {product.shortDescription ? (
                      <Text style={styles.productDescription} numberOfLines={3}>
                        {product.shortDescription}
                      </Text>
                    ) : null}
                    <View style={styles.productMetaRow}>
                      <Text style={styles.productPrice}>
                        {(product.priceCents / 100).toLocaleString('fr-FR', {
                          style: 'currency',
                          currency: 'EUR',
                        })}
                      </Text>
                      <Text style={styles.productStock}>{product.stock > 0 ? 'En stock' : 'Sur commande'}</Text>
                    </View>
                  </View>
                </Pressable>
              ))}
            </View>
          ) : null}
        </View>

        <View style={styles.ctaCard}>
          <Text style={styles.ctaTitle}>Prochaine étape</Text>
          <Text style={styles.ctaText}>
            Les onglets Produits et Compte sont maintenant alignés pour brancher la recherche,
            l’authentification et les données clients réelles.
          </Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

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
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 36,
    gap: 18,
  },
  heroCard: {
    backgroundColor: '#0c2436',
    borderRadius: 28,
    padding: 24,
    gap: 14,
    shadowColor: '#0c2436',
    shadowOpacity: 0.16,
    shadowRadius: 20,
    shadowOffset: { width: 0, height: 10 },
    elevation: 6,
  },
  heroEyebrow: {
    color: '#88d6ff',
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 2,
    textTransform: 'uppercase',
  },
  heroTitle: {
    color: '#ffffff',
    fontSize: 34,
    lineHeight: 38,
    fontWeight: '900',
  },
  heroText: {
    color: '#d5e7f3',
    fontSize: 15,
    lineHeight: 23,
  },
  heroPills: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginTop: 4,
  },
  heroPill: {
    borderRadius: 999,
    backgroundColor: '#17364c',
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
  heroPillText: {
    color: '#f5fbff',
    fontSize: 13,
    fontWeight: '700',
  },
  section: {
    gap: 8,
    paddingHorizontal: 4,
  },
  sectionTitle: {
    color: '#102b3e',
    fontSize: 24,
    fontWeight: '800',
  },
  sectionText: {
    color: '#5b7383',
    fontSize: 15,
    lineHeight: 22,
  },
  cardGrid: {
    gap: 14,
  },
  infoCard: {
    backgroundColor: '#ffffff',
    borderRadius: 22,
    padding: 20,
    gap: 10,
    borderWidth: 1,
    borderColor: '#d7e5ef',
  },
  infoCardTitle: {
    color: '#0f2f45',
    fontSize: 18,
    fontWeight: '800',
  },
  infoCardText: {
    color: '#5d7382',
    fontSize: 14,
    lineHeight: 21,
  },
  metricsCard: {
    backgroundColor: '#dff2ff',
    borderRadius: 24,
    padding: 20,
    gap: 16,
  },
  metricsTitle: {
    color: '#0f3550',
    fontSize: 20,
    fontWeight: '800',
  },
  metricsRow: {
    flexDirection: 'row',
    gap: 10,
  },
  metricItem: {
    flex: 1,
    backgroundColor: '#ffffff',
    borderRadius: 18,
    padding: 14,
    gap: 6,
  },
  metricValue: {
    color: '#0f7cc0',
    fontSize: 28,
    fontWeight: '900',
  },
  metricLabel: {
    color: '#527083',
    fontSize: 12,
    lineHeight: 17,
    fontWeight: '600',
  },
  ctaCard: {
    backgroundColor: '#ffffff',
    borderRadius: 24,
    padding: 20,
    gap: 8,
    borderWidth: 1,
    borderColor: '#d7e5ef',
  },
  ctaTitle: {
    color: '#122f44',
    fontSize: 20,
    fontWeight: '800',
  },
  ctaText: {
    color: '#5d7382',
    fontSize: 14,
    lineHeight: 22,
  },
  catalogSection: {
    gap: 14,
  },
  catalogTitle: {
    color: '#102b3e',
    fontSize: 24,
    fontWeight: '800',
    paddingHorizontal: 4,
  },
  catalogState: {
    minHeight: 92,
    borderRadius: 22,
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#d7e5ef',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    padding: 20,
  },
  catalogStateText: {
    color: '#5d7382',
    fontSize: 14,
    textAlign: 'center',
  },
  catalogErrorText: {
    color: '#b42318',
    fontSize: 14,
    textAlign: 'center',
    fontWeight: '600',
  },
  catalogList: {
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
    height: 190,
    backgroundColor: '#d9ebf5',
  },
  productImageFallback: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  productImageFallbackText: {
    color: '#0f7cc0',
    fontSize: 42,
    fontWeight: '900',
  },
  productContent: {
    gap: 8,
    padding: 18,
  },
  productBrand: {
    color: '#0f7cc0',
    fontSize: 12,
    fontWeight: '800',
    textTransform: 'uppercase',
    letterSpacing: 1.4,
  },
  productName: {
    color: '#102b3e',
    fontSize: 20,
    fontWeight: '800',
  },
  productDescription: {
    color: '#5d7382',
    fontSize: 14,
    lineHeight: 21,
  },
  productMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 12,
    marginTop: 4,
  },
  productPrice: {
    color: '#102b3e',
    fontSize: 18,
    fontWeight: '800',
  },
  productStock: {
    color: '#0f7cc0',
    fontSize: 13,
    fontWeight: '700',
  },
});
