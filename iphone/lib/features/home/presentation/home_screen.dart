import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/home_cards.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';

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
                const _WelcomeHero(),
                const SizedBox(height: 24),
                const _QuickActionsSection(),
                const SizedBox(height: 40),
                _SectionHeader(
                  title: 'Actualités',
                  actionLabel: 'Tout voir',
                  onPressed: () => context.push('/actualites'),
                ),
                const SizedBox(height: 24),
                newsAsync.when(
                  data: (articles) {
                    if (articles.isEmpty) {
                      return const _EmptySectionState(
                        message: 'Aucune actualité disponible pour le moment.',
                      );
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
                  },
                  error: (error, stackTrace) => _EmptySectionState(
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

class _WelcomeHero extends StatelessWidget {
  const _WelcomeHero();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: <Color>[
            Color(0xFF183B5B),
            Color(0xFF295C79),
            Color(0xFFCE7B36),
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
          Text(
            'Bienvenue sur l’application Hociatec',
            style: theme.textTheme.headlineSmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w900,
              height: 1.2,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'Retrouvez rapidement vos demandes de rendez-vous, d’audit, de devis et les dernières informations utiles.',
            style: theme.textTheme.bodyLarge?.copyWith(
              color: Colors.white.withValues(alpha: 0.88),
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickActionsSection extends StatelessWidget {
  const _QuickActionsSection();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 138,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: const <Widget>[
          _QuickActionCard(
            title: 'Rendez-vous',
            icon: Icons.calendar_month_outlined,
            route: '/prestations/rendez-vous',
          ),
          SizedBox(width: 12),
          _QuickActionCard(
            title: 'Audit',
            icon: Icons.fact_check_outlined,
            route: '/prestations/audit',
          ),
          SizedBox(width: 12),
          _QuickActionCard(
            title: 'Devis',
            icon: Icons.description_outlined,
            route: '/prestations/devis',
          ),
          SizedBox(width: 12),
          _QuickActionCard(
            title: 'Contact',
            icon: Icons.mail_outline,
            route: '/contact',
          ),
        ],
      ),
    );
  }
}

class _QuickActionCard extends StatelessWidget {
  const _QuickActionCard({
    required this.title,
    required this.icon,
    required this.route,
  });

  final String title;
  final IconData icon;
  final String route;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return SizedBox(
      width: 152,
      child: Card(
        child: InkWell(
          borderRadius: BorderRadius.circular(24),
          onTap: () => context.push(route),
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: <Widget>[
                Container(
                  width: 46,
                  height: 46,
                  decoration: BoxDecoration(
                    color: const Color(0xFF173751).withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Icon(icon, color: const Color(0xFF173751)),
                ),
                Text(
                  title,
                  maxLines: 2,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
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

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({
    required this.title,
    required this.actionLabel,
    required this.onPressed,
  });

  final String title;
  final String actionLabel;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Row(
      children: <Widget>[
        Container(
          width: 5,
          height: 32,
          decoration: BoxDecoration(
            color: const Color(0xFFCE7B36),
            borderRadius: BorderRadius.circular(10),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            title,
            style: theme.textTheme.headlineSmall?.copyWith(
              color: const Color(0xFF183B5B),
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        TextButton(
          onPressed: onPressed,
          child: Text(actionLabel),
        ),
      ],
    );
  }
}

class _EmptySectionState extends StatelessWidget {
  const _EmptySectionState({
    required this.message,
  });

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2D6C8)),
      ),
      child: Text(
        message,
        textAlign: TextAlign.center,
        style: Theme.of(context).textTheme.bodyMedium?.copyWith(
              color: const Color(0xFF5C544D),
              height: 1.5,
            ),
      ),
    );
  }
}
