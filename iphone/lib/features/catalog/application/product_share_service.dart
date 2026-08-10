import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/shared/utils/public_urls.dart';
import 'package:url_launcher/url_launcher.dart';

typedef UrlLauncher = Future<bool> Function(Uri uri, {LaunchMode mode});

enum ProductShareEmailResultKind {
  sent,
  fallbackOpened,
  fallbackFailed,
  validationError,
  failure,
}

class ProductShareEmailResult {
  const ProductShareEmailResult({
    required this.kind,
    required this.message,
  });

  final ProductShareEmailResultKind kind;
  final String message;

  bool get isSuccess =>
      kind == ProductShareEmailResultKind.sent ||
      kind == ProductShareEmailResultKind.fallbackOpened;
}

class ProductShareDraft {
  const ProductShareDraft({
    required this.publicUrl,
    required this.facebookUri,
    required this.mailtoUri,
  });

  final String publicUrl;
  final Uri facebookUri;
  final Uri mailtoUri;
}

class ProductShareService {
  ProductShareService(
    this._repository,
    this._siteBaseUrl, {
    UrlLauncher launch = launchUrl,
  }) : _launch = launch;

  final CatalogRepository _repository;
  final String _siteBaseUrl;
  final UrlLauncher _launch;

  static final RegExp _emailPattern = RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$');

  String? validateRecipientEmail(String value) {
    final normalized = value.trim();
    if (normalized.isEmpty) {
      return 'Veuillez renseigner une adresse e-mail.';
    }
    if (!_emailPattern.hasMatch(normalized)) {
      return 'Cette adresse e-mail ne semble pas valide.';
    }
    return null;
  }

  ProductShareDraft buildDraft({
    required CatalogProduct product,
    required String email,
  }) {
    final publicUrl = productPublicUrl(_siteBaseUrl, product.slug);
    return ProductShareDraft(
      publicUrl: publicUrl,
      facebookUri: facebookShareUri(publicUrl),
      mailtoUri: mailtoUri(
        email: email,
        subject: 'Decouvrir : ${product.displayName}',
        body: <String>[
          'Bonjour,',
          '',
          'Je te partage ce produit : ${product.displayName}',
          '',
          'Lien direct : $publicUrl',
          '',
          product.shortDescription ??
              'Consulte la fiche produit pour obtenir tous les details.',
        ].join('\n'),
      ),
    );
  }

  Future<ProductShareEmailResult> shareByEmail({
    required CatalogProduct product,
    required String email,
  }) async {
    final validationError = validateRecipientEmail(email);
    if (validationError != null) {
      return ProductShareEmailResult(
        kind: ProductShareEmailResultKind.validationError,
        message: validationError,
      );
    }

    final draft = buildDraft(product: product, email: email.trim());

    try {
      await _repository.shareProductByEmail(
        slug: product.slug,
        email: email.trim(),
      );
      return const ProductShareEmailResult(
        kind: ProductShareEmailResultKind.sent,
        message: 'Le produit a ete envoye par e-mail.',
      );
    } on DioException catch (error) {
      if (error.response?.statusCode == 503) {
        final opened = await _launch(
          draft.mailtoUri,
          mode: LaunchMode.externalApplication,
        );

        return ProductShareEmailResult(
          kind: opened
              ? ProductShareEmailResultKind.fallbackOpened
              : ProductShareEmailResultKind.fallbackFailed,
          message: opened
              ? 'Le service e-mail est indisponible. Votre messagerie a ete ouverte.'
              : 'Le service e-mail est indisponible et la messagerie n a pas pu etre ouverte.',
        );
      }

      final responseData = error.response?.data;
      final message = responseData is Map<String, dynamic>
          ? ((responseData['message'] as String?) ??
              "Impossible d'envoyer le produit par e-mail.")
          : "Impossible d'envoyer le produit par e-mail.";

      return ProductShareEmailResult(
        kind: ProductShareEmailResultKind.failure,
        message: message,
      );
    } catch (_) {
      return const ProductShareEmailResult(
        kind: ProductShareEmailResultKind.failure,
        message: "Impossible d'envoyer le produit par e-mail.",
      );
    }
  }
}

final productShareServiceProvider = Provider<ProductShareService>((ref) {
  final repository = ref.watch(catalogRepositoryProvider);
  final siteBaseUrl = ref.watch(siteBaseUrlProvider);
  return ProductShareService(repository, siteBaseUrl);
});
