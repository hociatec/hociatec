import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/quotes/data/quote_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/async_collection_view.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class MyQuotesScreen extends ConsumerWidget {
  const MyQuotesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final quotesAsync = ref.watch(myQuotesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes devis')),
      body: AsyncCollectionView<QuoteListItem>(
        asyncValue: quotesAsync,
        emptyMessage: 'Aucun devis enregistre.',
        errorFallback: 'Impossible de charger vos devis.',
        itemBuilder: (context, quote) => Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  quote.number,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 6),
                Text(quote.statusLabel),
                const SizedBox(height: 4),
                Text(
                  '${formatIsoDate(quote.createdAt)} • ${formatPriceCents(quote.totalTtcCents)}',
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
