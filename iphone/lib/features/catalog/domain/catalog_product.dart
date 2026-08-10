class CatalogCategorySummary {
  const CatalogCategorySummary({
    required this.id,
    required this.name,
    required this.slug,
  });

  factory CatalogCategorySummary.fromJson(Map<String, dynamic> json) {
    return CatalogCategorySummary(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] as String?)?.trim() ?? '',
      slug: (json['slug'] as String?)?.trim() ?? '',
    );
  }

  final int id;
  final String name;
  final String slug;
}

class ProductReviewSummary {
  const ProductReviewSummary({
    required this.count,
    required this.average,
  });

  factory ProductReviewSummary.fromJson(Map<String, dynamic> json) {
    return ProductReviewSummary(
      count: (json['count'] as num?)?.toInt() ?? 0,
      average: (json['average'] as num?)?.toDouble() ?? 0,
    );
  }

  final int count;
  final double average;
}

class CatalogProductGalleryItem {
  const CatalogProductGalleryItem({
    required this.position,
    required this.url,
    required this.alt,
    required this.isPrimary,
  });

  factory CatalogProductGalleryItem.fromJson(
    Map<String, dynamic> json, {
    required String baseUrl,
  }) {
    return CatalogProductGalleryItem(
      position: (json['position'] as num?)?.toInt() ?? 0,
      url: json['url'] as String? ?? '',
      alt: (json['alt'] as String?)?.trim() ?? '',
      isPrimary: json['isPrimary'] as bool? ?? false,
    );
  }

  final int position;
  final String url;
  final String alt;
  final bool isPrimary;
}

class CatalogProduct {
  const CatalogProduct({
    required this.id,
    required this.name,
    required this.slug,
    required this.sku,
    required this.shortDescription,
    required this.description,
    required this.priceCents,
    required this.effectivePriceCents,
    required this.sellingTypeLabel,
    required this.priceUnitLabel,
    required this.brand,
    required this.storageCapacity,
    required this.memoryRam,
    required this.color,
    required this.stock,
    required this.isFeaturedHome,
    required this.imageUrl,
    required this.imageAlt,
    required this.gallery,
    required this.category,
    required this.reviews,
  });

  factory CatalogProduct.fromJson(
    Map<String, dynamic> json, {
    required String baseUrl,
  }) {
    return CatalogProduct(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] as String?)?.trim() ?? '',
      slug: (json['slug'] as String?)?.trim() ?? '',
      sku: (json['sku'] as String?)?.trim() ?? '',
      shortDescription: (json['shortDescription'] as String?)?.trim(),
      description: (json['description'] as String?)?.trim() ?? '',
      priceCents: (json['priceCents'] as num?)?.toInt() ?? 0,
      effectivePriceCents:
          (json['effectivePriceCents'] as num?)?.toInt() ??
              (json['priceCents'] as num?)?.toInt() ??
              0,
      sellingTypeLabel: (json['sellingTypeLabel'] as String?)?.trim() ?? '',
      priceUnitLabel: (json['priceUnitLabel'] as String?)?.trim(),
      brand: (json['brand'] as String?)?.trim(),
      storageCapacity: (json['storageCapacity'] as String?)?.trim(),
      memoryRam: (json['memoryRam'] as String?)?.trim(),
      color: (json['color'] as String?)?.trim(),
      stock: (json['stock'] as num?)?.toInt() ?? 0,
      isFeaturedHome: json['isFeaturedHome'] as bool? ?? false,
      imageUrl: json['imageUrl'] as String? ?? '',
      imageAlt: (json['imageAlt'] as String?)?.trim(),
      gallery: ((json['gallery'] as List?) ?? const <dynamic>[])
          .whereType<Map>()
          .map((item) => CatalogProductGalleryItem.fromJson(
                item.cast<String, dynamic>(),
                baseUrl: baseUrl,
              ))
          .toList(growable: false),
      category: CatalogCategorySummary.fromJson(
        (json['category'] as Map?)?.cast<String, dynamic>() ??
            const <String, dynamic>{},
      ),
      reviews: ProductReviewSummary.fromJson(
        (json['reviews'] as Map?)?.cast<String, dynamic>() ??
            const <String, dynamic>{},
      ),
    );
  }

  final int id;
  final String name;
  final String slug;
  final String sku;
  final String? shortDescription;
  final String description;
  final int priceCents;
  final int effectivePriceCents;
  final String sellingTypeLabel;
  final String? priceUnitLabel;
  final String? brand;
  final String? storageCapacity;
  final String? memoryRam;
  final String? color;
  final int stock;
  final bool isFeaturedHome;
  final String imageUrl;
  final String? imageAlt;
  final List<CatalogProductGalleryItem> gallery;
  final CatalogCategorySummary category;
  final ProductReviewSummary reviews;

  String get displayName => name;
}
