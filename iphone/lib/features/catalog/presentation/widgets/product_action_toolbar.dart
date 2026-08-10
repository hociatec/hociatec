import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/cart/application/cart_controller.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/features/catalog/presentation/widgets/product_email_share_sheet.dart';
import 'package:hociatec_mobile/shared/utils/public_urls.dart';
import 'package:url_launcher/url_launcher.dart';

class ProductActionToolbar extends ConsumerWidget {
  const ProductActionToolbar({
    required this.product,
    this.compact = false,
    super.key,
  });

  final CatalogProduct product;
  final bool compact;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cartState = ref.watch(cartControllerProvider);
    final cartController = ref.read(cartControllerProvider.notifier);
    final pendingProductIds = ref.watch(pendingCartProductIdsProvider);
    final messenger = ScaffoldMessenger.of(context);
    final siteBaseUrl = ref.watch(siteBaseUrlProvider);
    final absoluteUrl = productPublicUrl(siteBaseUrl, product.slug);
    final isInCart = cartController.isProductInCart(product.id);
    final isPending = pendingProductIds.contains(product.id);

    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: <Widget>[
        FilledButton(
          onPressed: isPending
              ? null
              : () async {
                  try {
                    if (isInCart) {
                      await cartController.removeProduct(product.id);
                      messenger.showSnackBar(
                        const SnackBar(content: Text('Produit retire du panier.')),
                      );
                    } else {
                      await cartController.addProduct(product.id);
                      messenger.showSnackBar(
                        const SnackBar(content: Text('Produit ajoute au panier.')),
                      );
                    }
                  } catch (_) {
                    messenger.showSnackBar(
                      SnackBar(
                        content: Text(
                          isInCart
                              ? "Nous n'avons pas pu retirer cet article du panier."
                              : "Nous n'avons pas pu ajouter cet article au panier.",
                        ),
                      ),
                    );
                  }
                },
          style: FilledButton.styleFrom(
            padding: EdgeInsets.symmetric(
              horizontal: compact ? 14 : 18,
              vertical: compact ? 12 : 14,
            ),
          ),
          child: Text(
            isPending
                ? (isInCart ? 'Retrait...' : 'Ajout...')
                : (isInCart ? 'Retirer du panier' : 'Ajouter au panier'),
            style: const TextStyle(fontWeight: FontWeight.w800),
          ),
        ),
        OutlinedButton.icon(
          onPressed: () async {
            final opened = await launchUrl(
              facebookShareUri(absoluteUrl),
              mode: LaunchMode.externalApplication,
            );
            if (!opened && context.mounted) {
              messenger.showSnackBar(
                const SnackBar(content: Text('Impossible d’ouvrir le partage Facebook.')),
              );
            }
          },
          icon: const Icon(Icons.facebook, size: 18),
          label: const Text('Partager sur Facebook'),
          style: OutlinedButton.styleFrom(
            padding: EdgeInsets.symmetric(
              horizontal: compact ? 14 : 18,
              vertical: compact ? 12 : 14,
            ),
          ),
        ),
        OutlinedButton.icon(
          onPressed: () {
            showModalBottomSheet<void>(
              context: context,
              isScrollControlled: true,
              builder: (context) => ProductEmailShareSheet(product: product),
            );
          },
          icon: const Icon(Icons.mail_outline, size: 18),
          label: const Text('Partager par e-mail'),
          style: OutlinedButton.styleFrom(
            padding: EdgeInsets.symmetric(
              horizontal: compact ? 14 : 18,
              vertical: compact ? 12 : 14,
            ),
          ),
        ),
        if (cartState.valueOrNull != null && cartController.quantityForProduct(product.id) > 0)
          Padding(
            padding: const EdgeInsets.only(left: 4, top: 2),
            child: Text(
              'Dans le panier : ${cartController.quantityForProduct(product.id)}',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
            ),
          ),
      ],
    );
  }
}
