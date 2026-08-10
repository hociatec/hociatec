import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/features/catalog/presentation/widgets/product_action_toolbar.dart';
import 'package:hociatec_mobile/features/news/domain/news_article.dart';
import 'package:hociatec_mobile/features/services/domain/service_offering.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/fact_paragraph.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class HomeServiceCard extends StatelessWidget {
  const HomeServiceCard({
    required this.service,
    super.key,
  });

  final ServiceOffering service;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final description = extractFirstSentence(
      service.description,
      'Plus de details disponibles dans la fiche du service.',
    );

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => context.push('/prestations/${service.id}'),
      child: Ink(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFD6D0C9)),
          boxShadow: const <BoxShadow>[
            BoxShadow(
              color: Color(0x14342718),
              blurRadius: 26,
              offset: Offset(0, 10),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            _CardMedia(
              imageUrl: service.imageUrl,
              icon: Icons.design_services_outlined,
              background: const Color(0xFFF7F5F2),
              height: 132,
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    service.title,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w900,
                      color: const Color(0xFF171C24),
                      height: 1.25,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    description,
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: const Color(0xFF61574F),
                      height: 1.55,
                    ),
                  ),
                  const SizedBox(height: 14),
                  FactParagraph(
                    label: 'Mode de facturation',
                    value: formatServiceBillingMode(service.unit),
                  ),
                  FactParagraph(
                    label: 'Prix HT',
                    value: formatPriceCents(service.priceCents),
                  ),
                  FactParagraph(
                    label: 'Durée',
                    value: service.durationLabel?.isNotEmpty == true
                        ? service.durationLabel!
                        : 'Sur étude',
                    showDivider: false,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class HomeProductCard extends StatelessWidget {
  const HomeProductCard({
    required this.product,
    super.key,
  });

  final CatalogProduct product;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final specs = <String>[
      if ((product.brand ?? '').isNotEmpty) product.brand!,
      if ((product.memoryRam ?? '').isNotEmpty) product.memoryRam!,
      if ((product.storageCapacity ?? '').isNotEmpty) product.storageCapacity!,
      if ((product.color ?? '').isNotEmpty) product.color!,
    ].join(' • ');

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => context.push('/catalogue/produits/${product.slug}'),
      child: Ink(
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: <Color>[Color(0xFFFFFFFF), Color(0xFFFFFAF4)],
          ),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFDDD1C2)),
          boxShadow: const <BoxShadow>[
            BoxShadow(
              color: Color(0x122C1F10),
              blurRadius: 34,
              offset: Offset(0, 16),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            _CardMedia(
              imageUrl: product.imageUrl,
              icon: Icons.devices_outlined,
              background: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: <Color>[Color(0xFFFFF8EF), Color(0xFFF4FBFD)],
              ),
              height: 168,
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      product.displayName,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w900,
                        color: const Color(0xFF171C24),
                        height: 1.24,
                      ),
                    ),
                    const SizedBox(height: 8),
                    FactParagraph(label: 'Référence', value: product.sku),
                    FactParagraph(
                      label: 'Type',
                      value: '${product.category.name} (${product.sellingTypeLabel})',
                    ),
                    if (specs.isNotEmpty)
                      FactParagraph(
                        label: 'Configuration',
                        value: specs,
                        showDivider: false,
                      )
                    else
                      const SizedBox(height: 4),
                    if ((product.shortDescription ?? '').isNotEmpty) ...<Widget>[
                      const SizedBox(height: 10),
                      Text(
                        product.shortDescription!,
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: const Color(0xFF61574F),
                          height: 1.55,
                        ),
                      ),
                    ],
                    const Spacer(),
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.only(top: 10),
                      decoration: const BoxDecoration(
                        border: Border(
                          top: BorderSide(color: Color(0xFFE4DCD2)),
                        ),
                      ),
                      child: Text(
                        formatPriceCents(
                          product.effectivePriceCents,
                          suffix: product.priceUnitLabel,
                        ),
                        style: theme.textTheme.titleLarge?.copyWith(
                          color: const Color(0xFF9D5624),
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                    const SizedBox(height: 14),
                    ProductActionToolbar(
                      product: product,
                      compact: true,
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class HomeNewsCard extends StatelessWidget {
  const HomeNewsCard({
    required this.article,
    super.key,
  });

  final NewsArticle article;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => context.push('/actualites/${article.slug}'),
      child: Ink(
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: <Color>[Color(0xFFFFFFFF), Color(0xFFFFF9F0)],
          ),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFDDD1C2)),
          boxShadow: const <BoxShadow>[
            BoxShadow(
              color: Color(0x142C1F10),
              blurRadius: 30,
              offset: Offset(0, 18),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: <Widget>[
                  Text(
                    formatIsoDate(article.publishedAt ?? article.createdAt),
                    style: theme.textTheme.labelMedium?.copyWith(
                      color: const Color(0xFF73675B),
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  if ((article.category ?? '').isNotEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                      decoration: BoxDecoration(
                        color: const Color(0x1FF39A20),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        article.category!.toUpperCase(),
                        style: theme.textTheme.labelSmall?.copyWith(
                          color: const Color(0xFF9D5624),
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.8,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                article.title,
                style: theme.textTheme.titleLarge?.copyWith(
                  color: const Color(0xFF171C24),
                  fontWeight: FontWeight.w900,
                  height: 1.24,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                article.excerpt,
                maxLines: 4,
                overflow: TextOverflow.ellipsis,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF61574F),
                  height: 1.65,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CardMedia extends StatelessWidget {
  const _CardMedia({
    required this.imageUrl,
    required this.icon,
    required this.background,
    required this.height,
  });

  final String imageUrl;
  final IconData icon;
  final Object background;
  final double height;

  @override
  Widget build(BuildContext context) {
    final decoration = BoxDecoration(
      borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
      border: const Border(
        bottom: BorderSide(color: Color(0xFFDED8D1)),
      ),
      color: background is Color ? background as Color : null,
      gradient: background is Gradient ? background as Gradient : null,
    );

    return Container(
      decoration: decoration,
      padding: const EdgeInsets.all(18),
      child: SizedBox(
        height: height,
        width: double.infinity,
        child: imageUrl.isNotEmpty
            ? Image.network(
                imageUrl,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stackTrace) => _MediaPlaceholder(icon: icon),
              )
            : _MediaPlaceholder(icon: icon),
      ),
    );
  }
}

class _MediaPlaceholder extends StatelessWidget {
  const _MediaPlaceholder({
    required this.icon,
  });

  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: <Color>[Color(0x26F39A20), Color(0x2100A8B5)],
        ),
      ),
      child: Center(
        child: Icon(
          icon,
          size: 40,
          color: const Color(0xFF9D5624),
        ),
      ),
    );
  }
}
