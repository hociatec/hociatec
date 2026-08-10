import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/trade_ins/data/my_trade_in_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/async_collection_view.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class MyTradeInsScreen extends ConsumerWidget {
  const MyTradeInsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tradeInsAsync = ref.watch(myTradeInsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes reprises')),
      body: AsyncCollectionView<MyTradeInItem>(
        asyncValue: tradeInsAsync,
        emptyMessage: 'Aucune reprise enregistree.',
        errorFallback: 'Impossible de charger vos reprises.',
        itemBuilder: (context, tradeIn) => Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  tradeIn.reference,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 6),
                Text(tradeIn.productName),
                const SizedBox(height: 4),
                Text('${tradeIn.categoryLabel} • ${tradeIn.statusLabel}'),
                const SizedBox(height: 4),
                Text(formatIsoDate(tradeIn.createdAt)),
                if (tradeIn.offerCents != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child:
                        Text('Offre: ${formatPriceCents(tradeIn.offerCents!)}'),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
