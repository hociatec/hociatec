import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';
import 'package:hociatec_mobile/features/news/domain/news_article.dart';
import 'package:hociatec_mobile/features/services/data/services_repository.dart';
import 'package:hociatec_mobile/features/services/domain/service_offering.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/status_message_card.dart';
import 'package:hociatec_mobile/shared/utils/api_error_message.dart';

final searchableCatalogProductsProvider =
    FutureProvider<List<CatalogProduct>>((ref) async {
  final repository = ref.watch(catalogRepositoryProvider);
  final results =
      await Future.wait<List<CatalogProduct>>(<Future<List<CatalogProduct>>>[
    repository.fetchProductsBySellingType('sale'),
    repository.fetchProductsBySellingType('rental'),
  ]);

  final productsBySlug = <String, CatalogProduct>{};
  for (final products in results) {
    for (final product in products) {
      productsBySlug[product.slug] = product;
    }
  }

  return productsBySlug.values.toList(growable: false);
});

class SearchScreen extends ConsumerStatefulWidget {
  const SearchScreen({super.key});

  @override
  ConsumerState<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends ConsumerState<SearchScreen> {
  final TextEditingController _queryController = TextEditingController();
  String _query = '';

  @override
  void dispose() {
    _queryController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final productsAsync = ref.watch(searchableCatalogProductsProvider);
    final servicesAsync = ref.watch(allServicesProvider);
    final newsAsync = ref.watch(allNewsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Recherche')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        children: <Widget>[
          Text(
            'Recherche',
            style: theme.textTheme.headlineMedium?.copyWith(
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            'Recherchez rapidement un produit, une prestation ou une information utile dans l’application.',
            style: theme.textTheme.bodyLarge?.copyWith(
              color: const Color(0xFF5B544E),
              height: 1.45,
            ),
          ),
          const SizedBox(height: 20),
          TextField(
            controller: _queryController,
            onChanged: (value) => setState(() => _query = value.trim()),
            decoration: InputDecoration(
              hintText: 'Rechercher vente, location, audit, formation...',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: IconButton(
                onPressed: () {
                  _queryController.clear();
                  FocusManager.instance.primaryFocus?.unfocus();
                  setState(() => _query = '');
                },
                icon: Icon(_query.isEmpty ? Icons.tune_rounded : Icons.close),
              ),
            ),
          ),
          const SizedBox(height: 20),
          if (_query.isEmpty) ...const <Widget>[
            _SearchShortcutCard(
              title: 'Catalogue',
              description:
                  'Retrouvez les entrées vente, location, reprise et formation.',
              icon: Icons.grid_view_rounded,
              route: '/catalogue',
            ),
            SizedBox(height: 12),
            _SearchShortcutCard(
              title: 'Prestations',
              description:
                  'Accédez aux demandes de rendez-vous, devis et audit.',
              icon: Icons.design_services_outlined,
              route: '/prestations',
            ),
            SizedBox(height: 12),
            _SearchShortcutCard(
              title: 'Contact',
              description:
                  'Le formulaire de contact est disponible dans Contact.',
              icon: Icons.mail_outline,
              route: '/contact',
            ),
          ] else
            _SearchResultsSection(
              query: _query,
              productsAsync: productsAsync,
              servicesAsync: servicesAsync,
              newsAsync: newsAsync,
            ),
        ],
      ),
    );
  }
}

class _SearchResultsSection extends StatelessWidget {
  const _SearchResultsSection({
    required this.query,
    required this.productsAsync,
    required this.servicesAsync,
    required this.newsAsync,
  });

  final String query;
  final AsyncValue<List<CatalogProduct>> productsAsync;
  final AsyncValue<List<ServiceOffering>> servicesAsync;
  final AsyncValue<List<NewsArticle>> newsAsync;

  @override
  Widget build(BuildContext context) {
    final normalizedQuery = query.toLowerCase();

    final hasError =
        productsAsync.hasError || servicesAsync.hasError || newsAsync.hasError;
    if (hasError) {
      final error =
          productsAsync.error ?? servicesAsync.error ?? newsAsync.error;
      return StatusMessageCard(
        message: resolveApiErrorMessage(
          error!,
          'Impossible de lancer la recherche pour le moment.',
        ),
      );
    }

    if (productsAsync.isLoading ||
        servicesAsync.isLoading ||
        newsAsync.isLoading) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 32),
        child: Center(child: CircularProgressIndicator()),
      );
    }

    final productResults = productsAsync.valueOrNull
            ?.where((product) => _matches(
                  normalizedQuery,
                  <String?>[
                    product.displayName,
                    product.shortDescription,
                    product.category.name,
                    product.brand,
                    product.sellingTypeLabel,
                  ],
                ))
            .take(8)
            .toList(growable: false) ??
        const <CatalogProduct>[];

    final serviceResults = servicesAsync.valueOrNull
            ?.where((service) => _matches(
                  normalizedQuery,
                  <String?>[
                    service.title,
                    service.description,
                    service.durationLabel,
                  ],
                ))
            .take(8)
            .toList(growable: false) ??
        const <ServiceOffering>[];

    final newsResults = newsAsync.valueOrNull
            ?.where((article) => _matches(
                  normalizedQuery,
                  <String?>[
                    article.title,
                    article.excerpt,
                    article.category,
                  ],
                ))
            .take(8)
            .toList(growable: false) ??
        const <NewsArticle>[];

    final totalResults =
        productResults.length + serviceResults.length + newsResults.length;

    if (totalResults == 0) {
      return StatusMessageCard(
        message:
            'Aucun resultat pour "$query". Essayez un produit, une prestation ou une actualite.',
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(
          '$totalResults resultat(s) pour "$query"',
          style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w800,
              ),
        ),
        if (productResults.isNotEmpty) ...<Widget>[
          const SizedBox(height: 16),
          const _SearchSectionTitle(title: 'Produits'),
          const SizedBox(height: 10),
          ...productResults.map(
            (product) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _SearchResultTile(
                title: product.displayName,
                subtitle: product.shortDescription?.isNotEmpty == true
                    ? product.shortDescription!
                    : product.category.name,
                icon: Icons.devices_outlined,
                onTap: (context) =>
                    context.push('/catalogue/produits/${product.slug}'),
              ),
            ),
          ),
        ],
        if (serviceResults.isNotEmpty) ...<Widget>[
          const SizedBox(height: 10),
          const _SearchSectionTitle(title: 'Prestations'),
          const SizedBox(height: 10),
          ...serviceResults.map(
            (service) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _SearchResultTile(
                title: service.title,
                subtitle: service.description?.isNotEmpty == true
                    ? service.description!
                    : 'Prestation Hociatec',
                icon: Icons.design_services_outlined,
                onTap: (context) => context.push('/prestations/${service.id}'),
              ),
            ),
          ),
        ],
        if (newsResults.isNotEmpty) ...<Widget>[
          const SizedBox(height: 10),
          const _SearchSectionTitle(title: 'Actualites'),
          const SizedBox(height: 10),
          ...newsResults.map(
            (article) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _SearchResultTile(
                title: article.title,
                subtitle: article.excerpt,
                icon: Icons.article_outlined,
                onTap: (context) => context.push('/actualites/${article.slug}'),
              ),
            ),
          ),
        ],
      ],
    );
  }

  bool _matches(String query, List<String?> fields) {
    return fields.any(
      (field) => (field ?? '').toLowerCase().contains(query),
    );
  }
}

class _SearchSectionTitle extends StatelessWidget {
  const _SearchSectionTitle({
    required this.title,
  });

  final String title;

  @override
  Widget build(BuildContext context) {
    return Text(
      title,
      style: Theme.of(context).textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w900,
          ),
    );
  }
}

class _SearchShortcutCard extends StatelessWidget {
  const _SearchShortcutCard({
    required this.title,
    required this.description,
    required this.icon,
    required this.route,
  });

  final String title;
  final String description;
  final IconData icon;
  final String route;

  @override
  Widget build(BuildContext context) {
    return _SearchResultTile(
      title: title,
      subtitle: description,
      icon: icon,
      onTap: (context) => context.push(route),
    );
  }
}

class _SearchResultTile extends StatelessWidget {
  const _SearchResultTile({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final void Function(BuildContext context) onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(22),
        onTap: () => onTap(context),
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Row(
            children: <Widget>[
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: const Color(0xFF173751).withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(icon, color: const Color(0xFF173751)),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      title,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      subtitle,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: const Color(0xFF5B544E),
                        height: 1.45,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              const Icon(Icons.arrow_forward_ios_rounded, size: 18),
            ],
          ),
        ),
      ),
    );
  }
}
