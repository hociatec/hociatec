import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/features/catalog/application/product_share_service.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:url_launcher/url_launcher.dart';

class _FakeCatalogRepository extends CatalogRepository {
  _FakeCatalogRepository({
    this.shareError,
  }) : super(ApiClient(Dio()), 'https://api.hociatec.fr');

  final Object? shareError;
  String? sharedSlug;
  String? sharedEmail;

  @override
  Future<void> shareProductByEmail({
    required String slug,
    required String email,
  }) async {
    sharedSlug = slug;
    sharedEmail = email;
    if (shareError != null) throw shareError!;
  }
}

CatalogProduct _product() {
  return const CatalogProduct(
    id: 1,
    name: 'iPhone 16 Pro Max',
    slug: 'iphone-16-pro-max',
    sku: 'IPH16PM-REF-2026',
    shortDescription:
        'Grand iPhone reconditionne premium avec ecran 6,9 pouces, A18 Pro et photo haut de gamme.',
    description: 'Description',
    priceCents: 91000,
    effectivePriceCents: 91000,
    sellingTypeLabel: 'Vente',
    priceUnitLabel: null,
    brand: 'Apple',
    storageCapacity: '256 Go',
    memoryRam: '8 Go',
    color: 'Titane naturel',
    stock: 1,
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
  );
}

void main() {
  test('validates recipient email', () {
    final service = ProductShareService(
      _FakeCatalogRepository(),
      'https://hociatec.fr',
    );

    expect(service.validateRecipientEmail(''), 'Veuillez renseigner une adresse e-mail.');
    expect(
      service.validateRecipientEmail('not-an-email'),
      'Cette adresse e-mail ne semble pas valide.',
    );
    expect(service.validateRecipientEmail('client@example.com'), isNull);
  });

  test('builds draft from product and site url', () {
    final service = ProductShareService(
      _FakeCatalogRepository(),
      'https://hociatec.fr',
    );

    final draft = service.buildDraft(
      product: _product(),
      email: 'client@example.com',
    );

    expect(draft.publicUrl, 'https://hociatec.fr/catalogue/produits/iphone-16-pro-max');
    expect(
      draft.facebookUri.toString(),
      'https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fhociatec.fr%2Fcatalogue%2Fproduits%2Fiphone-16-pro-max',
    );
    expect(
      draft.mailtoUri.toString(),
      contains('mailto:client@example.com'),
    );
  });

  test('returns sent result when repository succeeds', () async {
    final repository = _FakeCatalogRepository();
    final service = ProductShareService(
      repository,
      'https://hociatec.fr',
    );

    final result = await service.shareByEmail(
      product: _product(),
      email: 'client@example.com',
    );

    expect(result.kind, ProductShareEmailResultKind.sent);
    expect(repository.sharedSlug, 'iphone-16-pro-max');
    expect(repository.sharedEmail, 'client@example.com');
  });

  test('opens mail fallback on 503', () async {
    final repository = _FakeCatalogRepository(
      shareError: DioException(
        requestOptions: RequestOptions(path: '/api/public/catalog/products/share'),
        response: Response<dynamic>(
          requestOptions: RequestOptions(path: '/api/public/catalog/products/share'),
          statusCode: 503,
        ),
      ),
    );
    Uri? openedUri;
    final service = ProductShareService(
      repository,
      'https://hociatec.fr',
      launch: (uri, {mode = LaunchMode.platformDefault}) async {
        openedUri = uri;
        return true;
      },
    );

    final result = await service.shareByEmail(
      product: _product(),
      email: 'client@example.com',
    );

    expect(result.kind, ProductShareEmailResultKind.fallbackOpened);
    expect(openedUri, isNotNull);
    expect(openedUri.toString(), contains('mailto:client@example.com'));
  });

  test('returns failure message on non-503 api error', () async {
    final repository = _FakeCatalogRepository(
      shareError: DioException(
        requestOptions: RequestOptions(path: '/api/public/catalog/products/share'),
        response: Response<Map<String, dynamic>>(
          requestOptions: RequestOptions(path: '/api/public/catalog/products/share'),
          statusCode: 400,
          data: <String, dynamic>{'message': 'Adresse refusée.'},
        ),
      ),
    );
    final service = ProductShareService(
      repository,
      'https://hociatec.fr',
    );

    final result = await service.shareByEmail(
      product: _product(),
      email: 'client@example.com',
    );

    expect(result.kind, ProductShareEmailResultKind.failure);
    expect(result.message, 'Adresse refusée.');
  });
}
