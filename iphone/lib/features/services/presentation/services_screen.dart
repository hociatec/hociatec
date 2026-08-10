import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/home_cards.dart';
import 'package:hociatec_mobile/features/services/data/services_repository.dart';

class ServicesScreen extends ConsumerWidget {
  const ServicesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final servicesAsync = ref.watch(allServicesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Prestations')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        children: <Widget>[
          Container(
            padding: const EdgeInsets.all(22),
            decoration: BoxDecoration(
              color: const Color(0xFFFBF7F0),
              borderRadius: BorderRadius.circular(28),
              border: Border.all(color: const Color(0xFFE3D8CA)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  'Nos services',
                  style: theme.textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w900,
                    color: const Color(0xFF173751),
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  'Retrouvez les accès rapides pour prendre rendez-vous, créer un devis ou demander un audit.',
                  style: theme.textTheme.bodyLarge?.copyWith(
                    height: 1.45,
                    color: const Color(0xFF5B544E),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          const _ServiceActionTile(
            icon: Icons.calendar_month_outlined,
            title: 'Prendre rendez-vous',
            subtitle:
                'Planifiez un créneau pour un échange, un diagnostic ou une intervention.',
          ),
          const SizedBox(height: 12),
          const _ServiceActionTile(
            icon: Icons.request_quote_outlined,
            title: 'Creer un devis',
            subtitle:
                'Préparez une demande de chiffrage pour votre besoin matériel ou service.',
          ),
          const SizedBox(height: 12),
          const _ServiceActionTile(
            icon: Icons.verified_user_outlined,
            title: 'Demander un audit',
            subtitle:
                'Faites analyser votre parc, votre sécurité ou vos usages.',
          ),
          const SizedBox(height: 28),
          Text(
            'Prestations disponibles',
            style: theme.textTheme.titleLarge
                ?.copyWith(fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 14),
          servicesAsync.when(
            data: (services) {
              if (services.isEmpty) {
                return const _ServicesStatusCard(
                  message: 'Aucune prestation disponible pour le moment.',
                );
              }

              return ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemBuilder: (context, index) =>
                    HomeServiceCard(service: services[index]),
                separatorBuilder: (context, index) =>
                    const SizedBox(height: 14),
                itemCount: services.length,
              );
            },
            error: (error, stackTrace) =>
                _ServicesStatusCard(message: error.toString()),
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

class _ServiceActionTile extends StatelessWidget {
  const _ServiceActionTile({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(24),
        onTap: () {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('$title disponible dans Prestations.')),
          );
        },
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

class _ServicesStatusCard extends StatelessWidget {
  const _ServicesStatusCard({
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
