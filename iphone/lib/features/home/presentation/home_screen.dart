import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/app/routing/app_router.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/home_cards.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';
import 'package:hociatec_mobile/features/news/domain/news_article.dart';
import 'package:hociatec_mobile/features/services/data/services_repository.dart';
import 'package:hociatec_mobile/features/services/domain/service_offering.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/section_block_header.dart';

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
              Color(0xFFFFFCF8),
              Color(0xFFF8F0E4),
              Color(0xFFFFFFFF),
            ],
          ),
        ),
        child: RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(featuredServicesProvider);
            ref.invalidate(featuredProductsProvider);
            ref.invalidate(latestNewsProvider);
            await Future.wait<void>(<Future<void>>[
              ref.read(featuredServicesProvider.future).then((_) {}),
              ref.read(featuredProductsProvider.future).then((_) {}),
              ref.read(latestNewsProvider.future).then((_) {}),
            ]);
          },
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: <Widget>[
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
                sliver: SliverList(
                  delegate: SliverChildListDelegate(
                    <Widget>[
                      const _WelcomeHero(),
                      const SizedBox(height: 24),
                      const _QuickEntryRow(),
                      const SizedBox(height: 36),
                      _SectionBlock(
                        eyebrow: 'Prestations en avant',
                        title: 'Nos interventions du moment',
                        subtitle:
                            'Réparation, accompagnement et support pour garder un service rapide sur mobile, poste et réseau.',
                        child: servicesAsync.when(
                          data: (services) =>
                              _ServicesCarousel(services: services),
                          error: (error, stackTrace) =>
                              _SectionError(message: error.toString()),
                          loading: () => const _SectionLoading(),
                        ),
                      ),
                      const SizedBox(height: 36),
                      _SectionBlock(
                        eyebrow: 'Selection catalogue',
                        title: 'Des produits prêts a partir',
                        subtitle:
                            'Retrouvez une sélection utile pour la vente, la location, la reprise et la formation.',
                        child: productsAsync.when(
                          data: (products) => _ProductsGrid(products: products),
                          error: (error, stackTrace) =>
                              _SectionError(message: error.toString()),
                          loading: () => const _SectionLoading(),
                        ),
                      ),
                      const SizedBox(height: 36),
                      _SectionBlock(
                        eyebrow: 'Conseils et actualites',
                        title: 'Rester informe',
                        subtitle:
                            'Les dernières informations utiles pour mieux choisir, protéger et faire évoluer vos équipements.',
                        child: newsAsync.when(
                          data: (articles) => _NewsColumn(articles: articles),
                          error: (error, stackTrace) =>
                              _SectionError(message: error.toString()),
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

class _WelcomeHero extends StatelessWidget {
  const _WelcomeHero();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: <Color>[
            Color(0xFF183B5B),
            Color(0xFF295C79),
            Color(0xFFCE7B36)
          ],
        ),
        borderRadius: BorderRadius.circular(30),
        boxShadow: const <BoxShadow>[
          BoxShadow(
            color: Color(0x261A2E40),
            blurRadius: 28,
            offset: Offset(0, 16),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.16),
              borderRadius: BorderRadius.circular(999),
            ),
            child: const Text(
              'Bienvenue sur l application Hociatec',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(height: 18),
          Text(
            'Votre espace mobile pour vendre, louer, reprendre et planifier vos services.',
            style: theme.textTheme.headlineSmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w900,
              height: 1.2,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'Accédez rapidement au catalogue, aux prestations, a la recherche et au contact depuis une seule navigation.',
            style: theme.textTheme.bodyLarge?.copyWith(
              color: Colors.white.withValues(alpha: 0.88),
              height: 1.5,
            ),
          ),
          const SizedBox(height: 24),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: <Widget>[
              FilledButton.icon(
                onPressed: () => context.go(AppTab.catalog.path),
                style: FilledButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: const Color(0xFF173751),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                ),
                icon: const Icon(Icons.grid_view_rounded),
                label: const Text('Voir le catalogue'),
              ),
              OutlinedButton.icon(
                onPressed: () => context.go(AppTab.services.path),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white,
                  side: const BorderSide(color: Colors.white70),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                ),
                icon: const Icon(Icons.design_services_outlined),
                label: const Text('Nos prestations'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _QuickEntryRow extends StatelessWidget {
  const _QuickEntryRow();

  @override
  Widget build(BuildContext context) {
    return const Column(
      children: <Widget>[
        _QuickEntryCard(
          icon: Icons.sell_outlined,
          title: 'Catalogue',
          description:
              'Vente, location, reprise et formation regroupées dans un seul onglet.',
          destination: AppTab.catalog,
        ),
        SizedBox(height: 12),
        _QuickEntryCard(
          icon: Icons.calendar_month_outlined,
          title: 'Prestations',
          description:
              'Prenez rendez-vous, créez un devis ou demandez un audit.',
          destination: AppTab.services,
        ),
        SizedBox(height: 12),
        _QuickEntryCard(
          icon: Icons.mail_outline,
          title: 'Contact',
          description:
              'Passez par le formulaire dans A propos pour nous écrire rapidement.',
          destination: AppTab.about,
        ),
      ],
    );
  }
}

class _QuickEntryCard extends StatelessWidget {
  const _QuickEntryCard({
    required this.icon,
    required this.title,
    required this.description,
    required this.destination,
  });

  final IconData icon;
  final String title;
  final String description;
  final AppTab destination;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return InkWell(
      borderRadius: BorderRadius.circular(22),
      onTap: () => context.go(destination.path),
      child: Ink(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: const Color(0xFFE4D8CA)),
        ),
        child: Row(
          children: <Widget>[
            Container(
              width: 52,
              height: 52,
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
                    description,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: const Color(0xFF5C544D),
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
    return Column(
      children: <Widget>[
        SectionBlockHeader(
          eyebrow: eyebrow,
          title: title,
          subtitle: subtitle,
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
      return const _SectionEmpty(
          message: 'Aucune prestation mise en avant pour le moment.');
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
      return const _SectionEmpty(
          message: 'Aucun produit mis en avant pour le moment.');
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
          itemBuilder: (context, index) =>
              HomeProductCard(product: products[index]),
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
      return const _SectionEmpty(
          message: 'Aucune actualite disponible pour le moment.');
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
