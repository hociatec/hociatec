import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/vouchers/data/voucher_repository.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class VouchersScreen extends ConsumerWidget {
  const VouchersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final vouchersAsync = ref.watch(myVouchersProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes bons')),
      body: vouchersAsync.when(
        data: (vouchers) {
          if (vouchers.isEmpty) {
            return const _EmptyState('Aucun bon disponible.');
          }

          return ListView.builder(
            padding: const EdgeInsets.all(20),
            itemCount: vouchers.length,
            itemBuilder: (context, index) {
              final voucher = vouchers[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        voucher.name,
                        style:
                            Theme.of(context).textTheme.titleMedium?.copyWith(
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
                          child: Text(
                              'Expire le ${formatIsoDate(voucher.endsAt)}'),
                        ),
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
