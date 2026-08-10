import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/trainings/data/my_training_repository.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class MyTrainingsScreen extends ConsumerWidget {
  const MyTrainingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final trainingsAsync = ref.watch(myTrainingEnrollmentsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes formations')),
      body: trainingsAsync.when(
        data: (enrollments) {
          if (enrollments.isEmpty) {
            return const _EmptyState('Aucune inscription en formation.');
          }

          return ListView.builder(
            padding: const EdgeInsets.all(20),
            itemCount: enrollments.length,
            itemBuilder: (context, index) {
              final enrollment = enrollments[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        enrollment.title,
                        style:
                            Theme.of(context).textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                      ),
                      const SizedBox(height: 6),
                      Text(enrollment.statusLabel),
                      const SizedBox(height: 4),
                      Text(
                          '${enrollment.formatLabel} • ${formatIsoDate(enrollment.scheduledStartsAt)}'),
                      const SizedBox(height: 4),
                      Text(formatPriceCents(enrollment.priceCents)),
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
