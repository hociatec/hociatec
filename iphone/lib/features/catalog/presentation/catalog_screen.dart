import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/home_cards.dart';

class CatalogScreen extends ConsumerWidget {
  const CatalogScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final productsAsync = ref.watch(featuredProductsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Catalogue')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        children: <Widget>[
          Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: <Color>[Color(0xFF183B5B), Color(0xFF346D74)],
              ),
              borderRadius: BorderRadius.circular(28),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  'Catalogue Hociatec',
                  style: theme.textTheme.headlineSmall?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  'Choisissez votre besoin principal puis retrouvez une sélection de produits mise en avant juste en dessous.',
                  style: theme.textTheme.bodyLarge?.copyWith(
                    color: Colors.white.withValues(alpha: 0.90),
                    height: 1.45,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          const _CatalogActionCard(
            title: 'Vente',
            subtitle: 'Commander du matériel prêt à être utilisé.',
            icon: Icons.shopping_bag_outlined,
          ),
          const SizedBox(height: 12),
          const _CatalogActionCard(
            title: 'Location',
            subtitle:
                'Accéder à des équipements sans immobiliser votre budget.',
            icon: Icons.event_available_outlined,
          ),
          const SizedBox(height: 12),
          const _CatalogActionCard(
            title: 'Reprise',
            subtitle:
                'Faire estimer vos anciens appareils pour leur donner une seconde vie.',
            icon: Icons.autorenew_rounded,
          ),
          const SizedBox(height: 12),
          const _CatalogActionCard(
            title: 'Formation',
            subtitle:
                'Monter en compétence sur les usages, outils et bonnes pratiques.',
            icon: Icons.school_outlined,
          ),
          const SizedBox(height: 28),
          Text(
            'Selection mise en avant',
            style: theme.textTheme.titleLarge
                ?.copyWith(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 14),
          productsAsync.when(
            data: (products) {
              if (products.isEmpty) {
                return const _CatalogEmptyState();
              }

              return GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
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
            error: (error, stackTrace) =>
                _CatalogStatusCard(message: error.toString()),
            loading: () => const Padding(
              padding: EdgeInsets.symmetric(vertical: 32),
              child: Center(child: CircularProgressIndicator()),
            ),
          ),
        ],
      ),
    );
  }
}

class _CatalogActionCard extends StatelessWidget {
  const _CatalogActionCard({
    required this.title,
    required this.subtitle,
    required this.icon,
  });

  final String title;
  final String subtitle;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return FilledButton.tonal(
      onPressed: () {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('$title disponible dans le catalogue.')),
        );
      },
      style: FilledButton.styleFrom(
        padding: const EdgeInsets.all(18),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(22)),
      ),
      child: Row(
        children: <Widget>[
          Icon(icon, size: 28),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  title,
                  style: theme.textTheme.titleMedium
                      ?.copyWith(fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 4),
                Text(
                  subtitle,
                  style: theme.textTheme.bodyMedium?.copyWith(height: 1.45),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          const Icon(Icons.arrow_forward_ios_rounded, size: 18),
        ],
      ),
    );
  }
}

class _CatalogEmptyState extends StatelessWidget {
  const _CatalogEmptyState();

  @override
  Widget build(BuildContext context) {
    return const _CatalogStatusCard(
      message: 'Aucun produit mis en avant dans le catalogue pour le moment.',
    );
  }
}

class _CatalogStatusCard extends StatelessWidget {
  const _CatalogStatusCard({
    required this.message,
  });

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE2D7CA)),
      ),
      child: Text(
        message,
        textAlign: TextAlign.center,
        style: Theme.of(context).textTheme.bodyLarge?.copyWith(
              fontWeight: FontWeight.w700,
            ),
      ),
    );
  }
}
