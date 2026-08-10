import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/home_cards.dart';

class CatalogListingScreen extends ConsumerWidget {
  const CatalogListingScreen({
    required this.title,
    required this.sellingType,
    super.key,
  });

  final String title;
  final String sellingType;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final future = ref.watch(catalogProductsBySellingTypeProvider(sellingType));

    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: future.when(
        data: (products) {
          if (products.isEmpty) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  'Aucun produit disponible pour $title.',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyLarge,
                ),
              ),
            );
          }

          return GridView.builder(
            padding: const EdgeInsets.all(20),
            itemCount: products.length,
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 1,
              childAspectRatio: 0.74,
              crossAxisSpacing: 14,
              mainAxisSpacing: 14,
            ),
            itemBuilder: (context, index) =>
                HomeProductCard(product: products[index]),
          );
        },
        error: (error, stackTrace) => Center(child: Text(error.toString())),
        loading: () => const Center(child: CircularProgressIndicator()),
      ),
    );
  }
}
