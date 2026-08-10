import 'package:flutter/material.dart';

class SearchScreen extends StatelessWidget {
  const SearchScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Recherche')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        children: <Widget>[
          Text(
            'Recherche',
            style: theme.textTheme.headlineMedium?.copyWith(
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            'Recherchez rapidement un produit, une prestation ou une information utile dans l’application.',
            style: theme.textTheme.bodyLarge?.copyWith(
              color: const Color(0xFF5B544E),
              height: 1.45,
            ),
          ),
          const SizedBox(height: 20),
          TextField(
            decoration: InputDecoration(
              hintText: 'Rechercher vente, location, audit, formation...',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: IconButton(
                onPressed: () {
                  FocusManager.instance.primaryFocus?.unfocus();
                },
                icon: const Icon(Icons.tune_rounded),
              ),
            ),
          ),
          const SizedBox(height: 20),
          const _SearchSuggestionCard(
            title: 'Catalogue',
            description:
                'Retrouvez les entrées vente, location, reprise et formation.',
            icon: Icons.grid_view_rounded,
          ),
          const SizedBox(height: 12),
          const _SearchSuggestionCard(
            title: 'Prestations',
            description: 'Accédez aux demandes de rendez-vous, devis et audit.',
            icon: Icons.design_services_outlined,
          ),
          const SizedBox(height: 12),
          const _SearchSuggestionCard(
            title: 'Contact',
            description:
                'Le formulaire de contact est disponible dans Contact.',
            icon: Icons.mail_outline,
          ),
        ],
      ),
    );
  }
}

class _SearchSuggestionCard extends StatelessWidget {
  const _SearchSuggestionCard({
    required this.title,
    required this.description,
    required this.icon,
  });

  final String title;
  final String description;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE2D7CA)),
      ),
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
                  description,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF5B544E),
                    height: 1.45,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
