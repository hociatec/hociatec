import 'package:flutter/material.dart';

class SectionBlockHeader extends StatelessWidget {
  const SectionBlockHeader({
    required this.eyebrow,
    required this.title,
    required this.subtitle,
    super.key,
  });

  final String eyebrow;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
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
    );
  }
}
