import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/vouchers/data/voucher_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/async_collection_view.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class VouchersScreen extends ConsumerWidget {
  const VouchersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final vouchersAsync = ref.watch(myVouchersProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes bons')),
      body: AsyncCollectionView<VoucherItem>(
        asyncValue: vouchersAsync,
        emptyMessage: 'Aucun bon disponible.',
        errorFallback: 'Impossible de charger vos bons.',
        itemBuilder: (context, voucher) => Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  voucher.name,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 6),
                Text('Code: ${voucher.code}'),
                const SizedBox(height: 4),
                Text('Valeur: ${voucher.valueLabel}'),
                const SizedBox(height: 4),
                Text(voucher.isActive ? 'Actif' : 'Inactif'),
                if (voucher.endsAt != null && voucher.endsAt!.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text('Expire le ${formatIsoDate(voucher.endsAt)}'),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
