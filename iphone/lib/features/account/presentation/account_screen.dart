import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/appointments/data/appointment_repository.dart';
import 'package:hociatec_mobile/features/audits/data/audit_repository.dart';
import 'package:hociatec_mobile/features/auth/data/auth_repository.dart';
import 'package:hociatec_mobile/features/quotes/data/quote_repository.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class AccountScreen extends ConsumerWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final userAsync = ref.watch(currentAuthUserProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mon compte')),
      body: userAsync.when(
        data: (user) {
          if (user == null) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    const Text(
                      'Vous devez vous connecter pour acceder a votre espace compte.',
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: () => context.push('/connexion'),
                      child: const Text('Se connecter'),
                    ),
                  ],
                ),
              ),
            );
          }

          return ListView(
            padding: const EdgeInsets.all(20),
            children: <Widget>[
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        user.displayName,
                        style:
                            Theme.of(context).textTheme.headlineSmall?.copyWith(
                                  fontWeight: FontWeight.w900,
                                ),
                      ),
                      const SizedBox(height: 8),
                      Text(user.email),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Text(
                'Aller a',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
              ),
              const SizedBox(height: 12),
              const Wrap(
                spacing: 10,
                runSpacing: 10,
                children: <Widget>[
                  _AccountLink(label: 'Commandes', path: '/compte/commandes'),
                  _AccountLink(label: 'Devis', path: '/compte/devis'),
                  _AccountLink(
                      label: 'Rendez-vous', path: '/compte/rendez-vous'),
                  _AccountLink(label: 'Audits', path: '/compte/audits'),
                  _AccountLink(label: 'Formations', path: '/compte/formations'),
                  _AccountLink(label: 'Bons', path: '/compte/bons'),
                  _AccountLink(label: 'Reprises', path: '/compte/reprises'),
                  _AccountLink(label: 'Favoris', path: '/compte/favoris'),
                ],
              ),
              const SizedBox(height: 24),
              _AccountSection<List<AppointmentItem>>(
                title: 'Mes rendez-vous',
                provider: myUpcomingAppointmentsProvider,
                builder: (items) {
                  if (items.isEmpty) {
                    return const _EmptyState(
                        message: 'Aucun rendez-vous a venir.');
                  }

                  return Column(
                    children: items
                        .map(
                          (item) => _InfoCard(
                            title: item.prestation.name,
                            subtitle:
                                '${formatIsoDate(item.startAt)} • ${item.status}',
                            detail:
                                'Duree ${item.prestation.durationMinutes} min',
                          ),
                        )
                        .toList(growable: false),
                  );
                },
              ),
              const SizedBox(height: 20),
              _AccountSection<List<AuditListItem>>(
                title: 'Mes audits',
                provider: myAuditsProvider,
                builder: (items) {
                  if (items.isEmpty) {
                    return const _EmptyState(
                        message: 'Aucun audit enregistre.');
                  }

                  return Column(
                    children: items
                        .map(
                          (item) => _InfoCard(
                            title: item.number,
                            subtitle: '${item.typeLabel} • ${item.statusLabel}',
                            detail: formatIsoDate(item.createdAt),
                          ),
                        )
                        .toList(growable: false),
                  );
                },
              ),
              const SizedBox(height: 20),
              _AccountSection<List<QuoteListItem>>(
                title: 'Mes devis',
                provider: myQuotesProvider,
                builder: (items) {
                  if (items.isEmpty) {
                    return const _EmptyState(
                        message: 'Aucun devis enregistre.');
                  }

                  return Column(
                    children: items
                        .map(
                          (item) => _InfoCard(
                            title: item.number,
                            subtitle: item.statusLabel,
                            detail:
                                '${formatIsoDate(item.createdAt)} • ${formatPriceCents(item.totalTtcCents)}',
                          ),
                        )
                        .toList(growable: false),
                  );
                },
              ),
            ],
          );
        },
        error: (error, stackTrace) => Center(child: Text(error.toString())),
        loading: () => const Center(child: CircularProgressIndicator()),
      ),
    );
  }
}

class _AccountLink extends StatelessWidget {
  const _AccountLink({
    required this.label,
    required this.path,
  });

  final String label;
  final String path;

  @override
  Widget build(BuildContext context) {
    return ActionChip(
      label: Text(label),
      onPressed: () => context.push(path),
    );
  }
}

class _AccountSection<T> extends ConsumerWidget {
  const _AccountSection({
    required this.title,
    required this.provider,
    required this.builder,
  });

  final String title;
  final FutureProvider<T> provider;
  final Widget Function(T data) builder;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncValue = ref.watch(provider);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(
          title,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w800,
              ),
        ),
        const SizedBox(height: 12),
        asyncValue.when(
          data: builder,
          error: (error, stackTrace) => _EmptyState(message: error.toString()),
          loading: () => const Center(child: CircularProgressIndicator()),
        ),
      ],
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({
    required this.title,
    required this.subtitle,
    required this.detail,
  });

  final String title;
  final String subtitle;
  final String detail;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
            ),
            const SizedBox(height: 6),
            Text(subtitle),
            const SizedBox(height: 4),
            Text(
              detail,
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({
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
        border: Border.all(color: const Color(0xFFE2D7CA)),
      ),
      child: Text(
        message,
        textAlign: TextAlign.center,
      ),
    );
  }
}
