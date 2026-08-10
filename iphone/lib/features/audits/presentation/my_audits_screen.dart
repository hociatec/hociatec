import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/audits/data/audit_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/async_collection_view.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class MyAuditsScreen extends ConsumerWidget {
  const MyAuditsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auditsAsync = ref.watch(myAuditsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes audits')),
      body: AsyncCollectionView<AuditListItem>(
        asyncValue: auditsAsync,
        emptyMessage: 'Aucun audit enregistre.',
        errorFallback: 'Impossible de charger vos audits.',
        itemBuilder: (context, audit) => Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  audit.number,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 6),
                Text('${audit.typeLabel} • ${audit.statusLabel}'),
                const SizedBox(height: 4),
                Text(formatIsoDate(audit.createdAt)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
