import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/orders/data/order_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/async_collection_view.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class OrdersScreen extends ConsumerWidget {
  const OrdersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ordersAsync = ref.watch(myOrdersProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes commandes')),
      body: AsyncCollectionView<OrderListItem>(
        asyncValue: ordersAsync,
        emptyMessage: 'Aucune commande enregistree.',
        errorFallback: 'Impossible de charger vos commandes.',
        itemBuilder: (context, order) => Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  order.number,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 6),
                Text(order.statusLabel),
                const SizedBox(height: 4),
                Text(
                  '${formatIsoDate(order.createdAt)} • ${formatPriceCents(order.totalPriceCents)}',
                ),
                const SizedBox(height: 4),
                Text('${order.itemCount} article(s)'),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
