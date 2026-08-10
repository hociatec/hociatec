import 'package:flutter/material.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/section_placeholder.dart';

class SearchScreen extends StatelessWidget {
  const SearchScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const SectionPlaceholder(
      title: 'Recherche',
      subtitle: 'Fondation de l\'écran de recherche prête à être développée.',
      icon: Icons.search_outlined,
    );
  }
}
