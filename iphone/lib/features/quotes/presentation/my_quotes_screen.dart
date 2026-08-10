import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/quotes/data/quote_repository.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class MyQuotesScreen extends ConsumerWidget {
  const MyQuotesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final quotesAsync = ref.watch(myQuotesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes devis')),
      body: quotesAsync.when(
        data: (quotes) {
          if (quotes.isEmpty) {
            return const _EmptyState('Aucun devis enregistre.');
          }

          return ListView.builder(
            padding: const EdgeInsets.all(20),
            itemCount: quotes.length,
            itemBuilder: (context, index) {
              final quote = quotes[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        quote.number,
                        style:
                            Theme.of(context).textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                      ),
                      const SizedBox(height: 6),
                      Text(quote.statusLabel),
                      const SizedBox(height: 4),
                      Text(
                          '${formatIsoDate(quote.createdAt)} • ${formatPriceCents(quote.totalTtcCents)}'),
                    ],
                  ),
                ),
              );
            },
          );
        },
        error: (error, stackTrace) => _EmptyState(error.toString()),
        loading: () => const Center(child: CircularProgressIndicator()),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState(this.message);

  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Text(
          message,
          textAlign: TextAlign.center,
        ),
      ),
    );
  }
}
