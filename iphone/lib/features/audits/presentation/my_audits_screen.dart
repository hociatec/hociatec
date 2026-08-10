import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/audits/data/audit_repository.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class MyAuditsScreen extends ConsumerWidget {
  const MyAuditsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auditsAsync = ref.watch(myAuditsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes audits')),
      body: auditsAsync.when(
        data: (audits) {
          if (audits.isEmpty) {
            return const _EmptyState('Aucun audit enregistre.');
          }

          return ListView.builder(
            padding: const EdgeInsets.all(20),
            itemCount: audits.length,
            itemBuilder: (context, index) {
              final audit = audits[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        audit.number,
                        style:
                            Theme.of(context).textTheme.titleMedium?.copyWith(
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
