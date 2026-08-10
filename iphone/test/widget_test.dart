import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hociatec_mobile/app/app.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';
import 'package:hociatec_mobile/features/news/domain/news_article.dart';
import 'package:hociatec_mobile/features/services/data/services_repository.dart';
import 'package:hociatec_mobile/features/services/domain/service_offering.dart';
import 'package:package_info_plus/package_info_plus.dart';

final _fakeServices = <ServiceOffering>[
  const ServiceOffering(
    id: 1,
    title: 'Diagnostic reseau',
    description: 'Audit et remise en etat de votre installation.',
    unit: 'intervention',
    isFeaturedHome: true,
    imageUrl: '',
    imageAlt: null,
    durationLabel: '2 h',
    priceCents: 8900,
  ),
];

final _fakeProducts = <CatalogProduct>[
  const CatalogProduct(
    id: 1,
    name: 'iPhone 13 Reconditionne',
    slug: 'iphone-13-reconditionne',
    sku: 'IP13-REF-128',
    shortDescription: 'Modele reconditionne controle.',
    description: 'Un smartphone teste et garanti.',
    priceCents: 49900,
    effectivePriceCents: 49900,
    sellingTypeLabel: 'achat',
    priceUnitLabel: null,
    brand: 'Apple',
    storageCapacity: '128 Go',
    memoryRam: '4 Go',
    color: 'Noir',
    stock: 3,
    isFeaturedHome: true,
    imageUrl: '',
    imageAlt: null,
    gallery: <CatalogProductGalleryItem>[],
    category: CatalogCategorySummary(
      id: 1,
      name: 'Smartphones',
      slug: 'smartphones',
    ),
    reviews: ProductReviewSummary(
      count: 0,
      average: 0,
    ),
  ),
];

final _fakeNews = <NewsArticle>[
  const NewsArticle(
    id: 1,
    title: 'Nouvelle actualite Hociatec',
    slug: 'nouvelle-actualite-hociatec',
    excerpt: 'Les dernieres nouveautes de la plateforme.',
    content: 'Contenu de demonstration.',
    category: 'Actualite',
    publishedAt: '2026-08-10T12:00:00Z',
    createdAt: '2026-08-10T12:00:00Z',
  ),
];

void main() {
  Finder navigationTab(String label) {
    return find.descendant(
      of: find.byType(NavigationBar),
      matching: find.text(label),
    );
  }

  setUpAll(() {
    PackageInfo.setMockInitialValues(
      appName: 'Hociatec',
      packageName: 'fr.hociatec.hociatecMobile',
      version: '1.0.0',
      buildNumber: '1',
      buildSignature: '',
    );
  });

  testWidgets('renders the four bottom navigation tabs', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          featuredServicesProvider.overrideWith((ref) async => _fakeServices),
          allServicesProvider.overrideWith((ref) async => _fakeServices),
          featuredProductsProvider.overrideWith((ref) async => _fakeProducts),
          latestNewsProvider.overrideWith((ref) async => _fakeNews),
        ],
        child: const HociatecMobileApp(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Accueil'), findsWidgets);
    expect(navigationTab('Recherche'), findsOneWidget);
    expect(navigationTab('Catalogue'), findsOneWidget);
    expect(navigationTab('Prestations'), findsOneWidget);
    expect(navigationTab('A propos'), findsOneWidget);
  });

  testWidgets('switches between tabs', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          featuredServicesProvider.overrideWith((ref) async => _fakeServices),
          allServicesProvider.overrideWith((ref) async => _fakeServices),
          featuredProductsProvider.overrideWith((ref) async => _fakeProducts),
          latestNewsProvider.overrideWith((ref) async => _fakeNews),
        ],
        child: const HociatecMobileApp(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Services mis en avant'), findsOneWidget);
    expect(find.text('Diagnostic reseau'), findsOneWidget);

    await tester.tap(navigationTab('Catalogue'));
    await tester.pumpAndSettle();
    expect(find.text('iPhone 13 Reconditionne'), findsOneWidget);

    await tester.tap(navigationTab('Prestations'));
    await tester.pumpAndSettle();
    expect(find.text('Diagnostic reseau'), findsOneWidget);

    await tester.tap(navigationTab('Recherche'));
    await tester.pumpAndSettle();
    expect(find.text('Fondation de l\'écran de recherche prête à être développée.'), findsOneWidget);
  });
}
