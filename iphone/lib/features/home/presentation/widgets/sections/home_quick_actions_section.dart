import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class HomeQuickActionsSection extends StatelessWidget {
  const HomeQuickActionsSection({super.key});

  @override
  Widget build(BuildContext context) {
    final actions = <HomeQuickAction>[
      const HomeQuickAction(
        title: 'Prendre rendez-vous',
        icon: Icons.calendar_month_outlined,
        route: '/prestations/rendez-vous',
      ),
      const HomeQuickAction(
        title: 'Demander un audit',
        icon: Icons.fact_check_outlined,
        route: '/prestations/audit',
      ),
      const HomeQuickAction(
        title: 'Créer un devis',
        icon: Icons.description_outlined,
        route: '/prestations/devis',
      ),
      const HomeQuickAction(
        title: 'Contact',
        icon: Icons.mail_outline,
        route: '/contact',
      ),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(
          'Accès rapides',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w800,
                color: const Color(0xFF173751),
              ),
        ),
        const SizedBox(height: 14),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: actions.length,
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: 1.1,
          ),
          itemBuilder: (context, index) => HomeQuickActionCard(
            action: actions[index],
          ),
        ),
      ],
    );
  }
}

class HomeQuickAction {
  const HomeQuickAction({
    required this.title,
    required this.icon,
    required this.route,
  });

  final String title;
  final IconData icon;
  final String route;
}

class HomeQuickActionCard extends StatelessWidget {
  const HomeQuickActionCard({
    required this.action,
    super.key,
  });

  final HomeQuickAction action;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return FilledButton(
      style: FilledButton.styleFrom(
        padding: const EdgeInsets.all(18),
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF173751),
        elevation: 0,
        side: const BorderSide(color: Color(0xFFE2D7CA)),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(24),
        ),
      ),
      onPressed: () => context.push(action.route),
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
            child: Icon(action.icon, color: const Color(0xFF173751)),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                action.title,
                maxLines: 2,
                textAlign: TextAlign.left,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: const Color(0xFF173751),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Ouvrir',
                style: theme.textTheme.labelLarge?.copyWith(
                  color: const Color(0xFF9D5624),
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
