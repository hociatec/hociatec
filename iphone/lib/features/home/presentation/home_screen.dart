import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/home_cards.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';
import 'package:hociatec_mobile/features/services/data/services_repository.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final servicesAsync = ref.watch(featuredServicesProvider);
    final productsAsync = ref.watch(featuredProductsProvider);
    final newsAsync = ref.watch(latestNewsProvider);

    return Scaffold(
      body: RefreshIndicator(
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
            SliverAppBar.large(
              pinned: true,
              title: const Text('Hociatec'),
              flexibleSpace: FlexibleSpaceBar(
                background: Container(
                  decoration: const BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: <Color>[
                        Color(0xFF16324F),
                        Color(0xFFB46A3A),
                      ],
                    ),
                  ),
                ),
              ),
            ),
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
              sliver: SliverList(
                delegate: SliverChildListDelegate(
                  <Widget>[
                    const HomeSectionHeader(
                      eyebrow: 'Interventions et accompagnement',
                      title: 'Services mis en avant',
                      subtitle:
                          'Des prestations concretes pour reparer, securiser, maintenir ou faire evoluer vos outils.',
                    ),
                    const SizedBox(height: 16),
                    servicesAsync.when(
                      data: (services) => _HorizontalCardList(
                        children: services
                            .map((service) => SizedBox(
                                  width: 296,
                                  child: HomeServiceCard(service: service),
                                ))
                            .toList(growable: false),
                      ),
                      error: (error, stackTrace) => _SectionError(message: error.toString()),
                      loading: () => const _SectionLoading(),
                    ),
                    const SizedBox(height: 32),
                    const HomeSectionHeader(
                      eyebrow: 'Catalogue selectionne',
                      title: 'Produits tendance',
                      subtitle:
                          'Une selection courte de materiel utile, lisible et directement actionnable.',
                    ),
                    const SizedBox(height: 16),
                    productsAsync.when(
                      data: (products) => GridView.builder(
                        itemCount: products.length,
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          childAspectRatio: 0.72,
                          crossAxisSpacing: 14,
                          mainAxisSpacing: 14,
                        ),
                        itemBuilder: (context, index) => HomeProductCard(
                          product: products[index],
                        ),
                      ),
                      error: (error, stackTrace) => _SectionError(message: error.toString()),
                      loading: () => const _SectionLoading(),
                    ),
                    const SizedBox(height: 32),
                    const HomeSectionHeader(
                      eyebrow: 'Veille et conseils',
                      title: 'Actualite',
                      subtitle:
                          'Les derniers contenus pour suivre les usages, la securite et les nouveautes Hociatec.',
                    ),
                    const SizedBox(height: 16),
                    newsAsync.when(
                      data: (articles) => Column(
                        children: articles
                            .map((article) => Padding(
                                  padding: const EdgeInsets.only(bottom: 14),
                                  child: HomeNewsCard(article: article),
                                ))
                            .toList(growable: false),
                      ),
                      error: (error, stackTrace) => _SectionError(message: error.toString()),
                      loading: () => const _SectionLoading(),
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

class _HorizontalCardList extends StatelessWidget {
  const _HorizontalCardList({
    required this.children,
  });

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 340,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemBuilder: (context, index) => children[index],
        separatorBuilder: (context, index) => const SizedBox(width: 14),
        itemCount: children.length,
      ),
    );
  }
}

class _SectionLoading extends StatelessWidget {
  const _SectionLoading();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 24),
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
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Text(
        message,
        style: TextStyle(
          color: Theme.of(context).colorScheme.error,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
