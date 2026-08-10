import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/features/catalog/presentation/widgets/product_action_toolbar.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/cards/card_media.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/fact_paragraph.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class CatalogProductCard extends StatelessWidget {
  const CatalogProductCard({
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
            CardMedia(
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
                      value:
                          '${product.category.name} (${product.sellingTypeLabel})',
                    ),
                    if (specs.isNotEmpty)
                      FactParagraph(
                        label: 'Configuration',
                        value: specs,
                        showDivider: false,
                      )
                    else
                      const SizedBox(height: 4),
                    if ((product.shortDescription ?? '')
                        .isNotEmpty) ...<Widget>[
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
