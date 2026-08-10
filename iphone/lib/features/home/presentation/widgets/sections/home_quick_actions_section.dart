import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

class HomeQuickActionsSection extends StatelessWidget {
  const HomeQuickActionsSection({super.key});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 138,
      child: ListView(
        scrollDirection: Axis.horizontal,
        children: const <Widget>[
          HomeQuickActionCard(
            title: 'Rendez-vous',
            icon: Icons.calendar_month_outlined,
            route: '/prestations/rendez-vous',
          ),
          SizedBox(width: 12),
          HomeQuickActionCard(
            title: 'Audit',
            icon: Icons.fact_check_outlined,
            route: '/prestations/audit',
          ),
          SizedBox(width: 12),
          HomeQuickActionCard(
            title: 'Devis',
            icon: Icons.description_outlined,
            route: '/prestations/devis',
          ),
          SizedBox(width: 12),
          HomeQuickActionCard(
            title: 'Contact',
            icon: Icons.mail_outline,
            route: '/contact',
          ),
        ],
      ),
    );
  }
}

class HomeQuickActionCard extends StatelessWidget {
  const HomeQuickActionCard({
    required this.title,
    required this.icon,
    required this.route,
    super.key,
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
