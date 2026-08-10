import 'package:flutter/material.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/section_placeholder.dart';

class ServicesScreen extends StatelessWidget {
  const ServicesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const SectionPlaceholder(
      title: 'Prestations',
      subtitle: 'Fondation de l\'écran prestations prête à être développée.',
      icon: Icons.design_services_outlined,
    );
  }
}
