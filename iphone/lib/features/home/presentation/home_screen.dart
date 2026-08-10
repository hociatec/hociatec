import 'package:flutter/material.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/section_placeholder.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const SectionPlaceholder(
      title: 'Accueil',
      subtitle: 'Fondation de l\'écran d\'accueil prête à être développée.',
      icon: Icons.home_outlined,
    );
  }
}
