import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/home_cards.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';
import 'package:hociatec_mobile/features/news/domain/news_article.dart';
import 'package:hociatec_mobile/features/services/data/services_repository.dart';
import 'package:hociatec_mobile/features/services/domain/service_offering.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final servicesAsync = ref.watch(featuredServicesProvider);
    final productsAsync = ref.watch(featuredProductsProvider);
    final newsAsync = ref.watch(latestNewsProvider);

    return Scaffold(
      body: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: <Color>[
              Color(0xFFFFFCF7),
              Color(0xFFF9F3E9),
              Color(0xFFFFFFFF),
            ],
            stops: <double>[0, 0.38, 1],
          ),
        ),
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(featuredServicesProvider);
            ref.invalidate(featuredProductsProvider);
            ref.invalidate(latestNewsProvider);
            await Future.wait(<Future<void>>[
              ref.read(featuredServicesProvider.future).then((_) {}),
              ref.read(featuredProductsProvider.future).then((_) {}),
              ref.read(latestNewsProvider.future).then((_) {}),
            ]);
          },
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: <Widget>[
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
                sliver: SliverList(
                  delegate: SliverChildListDelegate(
                    <Widget>[
                      _SectionBlock(
                        eyebrow: 'Interventions et accompagnement',
                        title: 'Services mis en avant',
                        subtitle:
                            'Des prestations concrètes pour réparer, sécuriser, maintenir ou faire évoluer vos outils.',
                        child: servicesAsync.when(
                          data: (services) => _ServicesCarousel(services: services),
                          error: (error, stackTrace) => _SectionError(message: error.toString()),
                          loading: () => const _SectionLoading(),
                        ),
                      ),
                      const SizedBox(height: 40),
                      _SectionBlock(
                        eyebrow: 'Catalogue sélectionné',
                        title: 'Produits recommandés',
                        subtitle:
                            'Une sélection courte de matériel utile, lisible et directement actionnable.',
                        child: productsAsync.when(
                          data: (products) => _ProductsGrid(products: products),
                          error: (error, stackTrace) => _SectionError(message: error.toString()),
                          loading: () => const _SectionLoading(),
                        ),
                      ),
                      const SizedBox(height: 40),
                      _SectionBlock(
                        eyebrow: 'Veille et conseils',
                        title: 'Actualité',
                        subtitle:
                            'Les derniers contenus pour suivre les usages, la sécurité et les nouveautés Hociatec.',
                        child: newsAsync.when(
                          data: (articles) => _NewsColumn(articles: articles),
                          error: (error, stackTrace) => _SectionError(message: error.toString()),
                          loading: () => const _SectionLoading(),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionBlock extends StatelessWidget {
  const _SectionBlock({
    required this.eyebrow,
    required this.title,
    required this.subtitle,
    required this.child,
  });

  final String eyebrow;
  final String title;
  final String subtitle;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      children: <Widget>[
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 4),
          child: Column(
            children: <Widget>[
              Text(
                eyebrow,
                textAlign: TextAlign.center,
                style: theme.textTheme.labelLarge?.copyWith(
                  color: const Color(0xFF9D5624),
                  fontWeight: FontWeight.w900,
                  letterSpacing: 1.2,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                title,
                textAlign: TextAlign.center,
                style: theme.textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                  color: const Color(0xFF1D2430),
                ),
              ),
              const SizedBox(height: 12),
              Container(
                width: 84,
                height: 6,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(999),
                  gradient: const LinearGradient(
                    colors: <Color>[
                      Color(0xFFF39A20),
                      Color(0xFFB46A3A),
                      Color(0xFF00A8B5),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 14),
              ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Text(
                  subtitle,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF5D5750),
                    height: 1.65,
                  ),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        child,
      ],
    );
  }
}

class _ServicesCarousel extends StatefulWidget {
  const _ServicesCarousel({
    required this.services,
  });

  final List<ServiceOffering> services;

  @override
  State<_ServicesCarousel> createState() => _ServicesCarouselState();
}

class _ServicesCarouselState extends State<_ServicesCarousel> {
  final PageController _controller = PageController(viewportFraction: 0.9);
  int _activeIndex = 0;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (widget.services.isEmpty) {
      return const _SectionEmpty(message: 'Aucun service mis en avant pour le moment.');
    }

    return Column(
      children: <Widget>[
        SizedBox(
          height: 490,
          child: PageView.builder(
            controller: _controller,
            padEnds: false,
            onPageChanged: (index) => setState(() => _activeIndex = index),
            itemCount: widget.services.length,
            itemBuilder: (context, index) => Padding(
              padding: EdgeInsets.only(
                left: index == 0 ? 0 : 12,
                right: index == widget.services.length - 1 ? 0 : 12,
              ),
              child: HomeServiceCard(service: widget.services[index]),
            ),
          ),
        ),
        if (widget.services.length > 1) ...<Widget>[
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            children: List<Widget>.generate(
              widget.services.length,
              (index) => AnimatedContainer(
                duration: const Duration(milliseconds: 180),
                width: index == _activeIndex ? 24 : 8,
                height: 8,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(999),
                  gradient: index == _activeIndex
                      ? const LinearGradient(
                          colors: <Color>[Color(0xFFF39A20), Color(0xFF00A8B5)],
                        )
                      : null,
                  color: index == _activeIndex ? null : const Color(0x3312110F),
                ),
              ),
            ),
          ),
        ],
      ],
    );
  }
}

class _ProductsGrid extends StatelessWidget {
  const _ProductsGrid({
    required this.products,
  });

  final List<CatalogProduct> products;

  @override
  Widget build(BuildContext context) {
    if (products.isEmpty) {
      return const _SectionEmpty(message: 'Aucun produit mis en avant pour le moment.');
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        final crossAxisCount = constraints.maxWidth >= 760 ? 2 : 1;

        return GridView.builder(
          itemCount: products.length,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: crossAxisCount,
            childAspectRatio: crossAxisCount == 1 ? 0.74 : 0.48,
            crossAxisSpacing: 16,
            mainAxisSpacing: 16,
          ),
          itemBuilder: (context, index) => HomeProductCard(product: products[index]),
        );
      },
    );
  }
}

class _NewsColumn extends StatelessWidget {
  const _NewsColumn({
    required this.articles,
  });

  final List<NewsArticle> articles;

  @override
  Widget build(BuildContext context) {
    if (articles.isEmpty) {
      return const _SectionEmpty(message: 'Aucune actualité disponible pour le moment.');
    }

    return Column(
      children: articles
          .map(
            (article) => Padding(
              padding: const EdgeInsets.only(bottom: 14),
              child: HomeNewsCard(article: article),
            ),
          )
          .toList(growable: false),
    );
  }
}

class _SectionLoading extends StatelessWidget {
  const _SectionLoading();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 32),
      child: Center(child: CircularProgressIndicator()),
    );
  }
}

class _SectionError extends StatelessWidget {
  const _SectionError({
    required this.message,
  });

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF4F2),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFF1C9C1)),
      ),
      child: Text(
        message,
        style: TextStyle(
          color: Theme.of(context).colorScheme.error,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _SectionEmpty extends StatelessWidget {
  const _SectionEmpty({
    required this.message,
  });

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE4DACD)),
      ),
      child: Text(
        message,
        textAlign: TextAlign.center,
        style: Theme.of(context).textTheme.bodyLarge?.copyWith(
              color: const Color(0xFF514B45),
              fontWeight: FontWeight.w700,
            ),
      ),
    );
  }
}
