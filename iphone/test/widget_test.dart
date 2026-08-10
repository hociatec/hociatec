import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hociatec_mobile/app/app.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/features/appointments/data/appointment_repository.dart';
import 'package:hociatec_mobile/features/appointments/presentation/appointment_request_screen.dart';
import 'package:hociatec_mobile/features/audits/data/audit_repository.dart';
import 'package:hociatec_mobile/features/audits/presentation/audit_request_screen.dart';
import 'package:hociatec_mobile/features/auth/data/auth_repository.dart';
import 'package:hociatec_mobile/features/auth/data/auth_session_store.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/features/contact/data/contact_repository.dart';
import 'package:hociatec_mobile/features/contact/presentation/contact_screen.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';
import 'package:hociatec_mobile/features/news/domain/news_article.dart';
import 'package:hociatec_mobile/features/services/data/services_repository.dart';
import 'package:hociatec_mobile/features/services/domain/service_offering.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:shared_preferences/shared_preferences.dart';

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

final _fakePrestations = <AppointmentPrestation>[
  const AppointmentPrestation(
    id: 1,
    name: 'Diagnostic atelier',
    durationMinutes: 60,
    priceCents: 6900,
  ),
];

final _fakeAuditTypes = <AuditTypeOption>[
  const AuditTypeOption(value: 'performance', label: 'Performance'),
  const AuditTypeOption(value: 'security', label: 'Sécurité'),
];

class _FakeContactRepository extends ContactRepository {
  _FakeContactRepository() : super(ApiClient(Dio()));

  int submitCount = 0;

  @override
  Future<String> submit({
    required String name,
    required String email,
    required String subject,
    required String message,
  }) async {
    submitCount += 1;
    return 'Votre message a bien été envoyé.';
  }
}

void main() {
  Finder navigationTab(String label) {
    return find.descendant(
      of: find.byType(NavigationBar),
      matching: find.text(label),
    );
  }

  late SharedPreferences preferences;

  setUpAll(() async {
    PackageInfo.setMockInitialValues(
      appName: 'Hociatec',
      packageName: 'fr.hociatec.hociatecMobile',
      version: '1.0.0',
      buildNumber: '1',
      buildSignature: '',
    );
    SharedPreferences.setMockInitialValues(<String, Object>{});
    preferences = await SharedPreferences.getInstance();
  });

  testWidgets('renders the bottom navigation tabs', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authSessionStoreProvider.overrideWithValue(
            AuthSessionStore(preferences),
          ),
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
    expect(navigationTab('À propos'), findsOneWidget);
  });

  testWidgets('switches between tabs', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authSessionStoreProvider.overrideWithValue(
            AuthSessionStore(preferences),
          ),
          featuredServicesProvider.overrideWith((ref) async => _fakeServices),
          allServicesProvider.overrideWith((ref) async => _fakeServices),
          featuredProductsProvider.overrideWith((ref) async => _fakeProducts),
          latestNewsProvider.overrideWith((ref) async => _fakeNews),
        ],
        child: const HociatecMobileApp(),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Bienvenue sur l’application Hociatec'), findsOneWidget);
    expect(find.text('Nous contacter'), findsOneWidget);

    await tester.tap(navigationTab('Catalogue'));
    await tester.pumpAndSettle();
    expect(find.text('Catalogue Hociatec'), findsOneWidget);
    expect(find.text('Vente'), findsOneWidget);

    await tester.tap(navigationTab('Prestations'));
    await tester.pumpAndSettle();
    expect(find.text('Nos services'), findsOneWidget);
    expect(find.text('Prendre rendez-vous'), findsOneWidget);

    await tester.tap(navigationTab('Recherche'));
    await tester.pumpAndSettle();
    expect(
      find.text(
        'Recherchez rapidement un produit, une prestation ou une information utile dans l’application.',
      ),
      findsOneWidget,
    );
  });

  testWidgets('opens contact from home screen', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authSessionStoreProvider.overrideWithValue(
            AuthSessionStore(preferences),
          ),
          currentAuthUserProvider.overrideWith((ref) async => null),
          featuredServicesProvider.overrideWith((ref) async => _fakeServices),
          allServicesProvider.overrideWith((ref) async => _fakeServices),
          featuredProductsProvider.overrideWith((ref) async => _fakeProducts),
          latestNewsProvider.overrideWith((ref) async => _fakeNews),
        ],
        child: const HociatecMobileApp(),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(find.text('Contact').first);
    await tester.pumpAndSettle();

    expect(find.text('Formulaire'), findsOneWidget);
    expect(find.text('Envoyer la demande'), findsOneWidget);
  });

  testWidgets('about screen no longer embeds the contact form', (tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authSessionStoreProvider.overrideWithValue(
            AuthSessionStore(preferences),
          ),
          currentAuthUserProvider.overrideWith((ref) async => null),
          featuredServicesProvider.overrideWith((ref) async => _fakeServices),
          allServicesProvider.overrideWith((ref) async => _fakeServices),
          featuredProductsProvider.overrideWith((ref) async => _fakeProducts),
          latestNewsProvider.overrideWith((ref) async => _fakeNews),
        ],
        child: const HociatecMobileApp(),
      ),
    );
    await tester.pumpAndSettle();

    await tester.tap(navigationTab('À propos'));
    await tester.pumpAndSettle();

    expect(find.text('Ouvrir le contact'), findsOneWidget);
    expect(find.text('Formulaire'), findsNothing);
    expect(find.text('Envoyer la demande'), findsNothing);
  });

  testWidgets('appointment request requires login and blocks submit', (
    tester,
  ) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          currentAuthUserProvider.overrideWith((ref) async => null),
          allServicesProvider.overrideWith((ref) async => _fakeServices),
          publicAppointmentPrestationsProvider.overrideWith(
            (ref) async => _fakePrestations,
          ),
        ],
        child: const MaterialApp(
          home: AppointmentRequestScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(
      find.text('Connexion requise pour confirmer le rendez-vous.'),
      findsOneWidget,
    );

    final button = tester.widget<FilledButton>(
      find.widgetWithText(FilledButton, 'Confirmer le rendez-vous'),
    );
    expect(button.onPressed, isNull);
  });

  testWidgets('audit request requires login and blocks submit', (
    tester,
  ) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          currentAuthUserProvider.overrideWith((ref) async => null),
          allServicesProvider.overrideWith((ref) async => _fakeServices),
          auditTypesProvider.overrideWith((ref) async => _fakeAuditTypes),
        ],
        child: const MaterialApp(
          home: AuditRequestScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(
      find.text('Connexion requise pour envoyer la demande d’audit.'),
      findsOneWidget,
    );

    final button = tester.widget<FilledButton>(
      find.widgetWithText(FilledButton, 'Envoyer la demande'),
    );
    expect(button.onPressed, isNull);
  });

  testWidgets('contact form submits and clears fields', (tester) async {
    final fakeRepository = _FakeContactRepository();
    tester.view.physicalSize = const Size(800, 1400);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          contactRepositoryProvider.overrideWithValue(fakeRepository),
        ],
        child: const MaterialApp(
          home: ContactScreen(),
        ),
      ),
    );
    await tester.pumpAndSettle();

    await tester.enterText(
        find.widgetWithText(TextFormField, 'Nom'), 'Camille');
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Email'),
      'camille@example.com',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Sujet'),
      'Demande de devis',
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Message'),
      'Bonjour, je souhaite obtenir plus d informations.',
    );

    final submitButton =
        find.widgetWithText(FilledButton, 'Envoyer la demande');
    await tester.ensureVisible(submitButton);
    await tester.tap(submitButton);
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));

    expect(fakeRepository.submitCount, 1);
    expect(find.text('Votre message a bien été envoyé.'), findsOneWidget);
    expect(find.text('Camille'), findsNothing);
    expect(find.text('camille@example.com'), findsNothing);
  });
}
