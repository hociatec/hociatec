import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/sections/home_empty_section_state.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/sections/home_quick_actions_section.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/sections/home_section_header.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/sections/home_welcome_hero.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/cards/app_cards.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final newsAsync = ref.watch(latestNewsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Accueil'),
        actions: <Widget>[
          TextButton.icon(
            onPressed: () => context.push('/contact'),
            icon: const Icon(Icons.mail_outline),
            label: const Text('Contact'),
          ),
          const SizedBox(width: 8),
        ],
      ),
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
        child: SafeArea(
          child: RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(latestNewsProvider);
              await ref.read(latestNewsProvider.future);
            },
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(24),
              children: <Widget>[
                const HomeWelcomeHero(),
                const SizedBox(height: 24),
                const HomeQuickActionsSection(),
                const SizedBox(height: 40),
                HomeSectionHeader(
                  title: 'Actualités',
                  actionLabel: 'Tout voir',
                  onPressed: () => context.push('/actualites'),
                ),
                const SizedBox(height: 24),
                newsAsync.when(
                  data: (articles) {
                    if (articles.isEmpty) {
                      return const HomeEmptySectionState(
                        message: 'Aucune actualité disponible pour le moment.',
                      );
                    }

                    return Column(
                      children: articles
                          .map(
                            (article) => Padding(
                              padding: const EdgeInsets.only(bottom: 14),
                              child: NewsArticleCard(article: article),
                            ),
                          )
                          .toList(growable: false),
                    );
                  },
                  error: (error, stackTrace) => HomeEmptySectionState(
                    message: error.toString(),
                  ),
                  loading: () => const Padding(
                    padding: EdgeInsets.symmetric(vertical: 32),
                    child: Center(child: CircularProgressIndicator()),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
