import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/trainings/data/my_training_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/async_collection_view.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class MyTrainingsScreen extends ConsumerWidget {
  const MyTrainingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final trainingsAsync = ref.watch(myTrainingEnrollmentsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes formations')),
      body: AsyncCollectionView<MyTrainingEnrollment>(
        asyncValue: trainingsAsync,
        emptyMessage: 'Aucune inscription en formation.',
        errorFallback: 'Impossible de charger vos formations.',
        itemBuilder: (context, enrollment) => Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  enrollment.title,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 6),
                Text(enrollment.statusLabel),
                const SizedBox(height: 4),
                Text(
                  '${enrollment.formatLabel} • ${formatIsoDate(enrollment.scheduledStartsAt)}',
                ),
                const SizedBox(height: 4),
                Text(formatPriceCents(enrollment.priceCents)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
