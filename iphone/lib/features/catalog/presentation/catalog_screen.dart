import 'package:flutter/material.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/section_placeholder.dart';

class CatalogScreen extends StatelessWidget {
  const CatalogScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const SectionPlaceholder(
      title: 'Catalogue',
      subtitle: 'Fondation de l\'écran catalogue prête à être développée.',
      icon: Icons.grid_view_outlined,
    );
  }
}
