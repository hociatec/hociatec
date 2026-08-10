import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/appointments/data/appointment_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/async_collection_view.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class MyAppointmentsScreen extends ConsumerWidget {
  const MyAppointmentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final appointmentsAsync = ref.watch(myUpcomingAppointmentsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Mes rendez-vous')),
      body: AsyncCollectionView<AppointmentItem>(
        asyncValue: appointmentsAsync,
        emptyMessage: 'Aucun rendez-vous a venir.',
        errorFallback: 'Impossible de charger vos rendez-vous.',
        itemBuilder: (context, appointment) => Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text(
                  appointment.prestation.name,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 6),
                Text(appointment.status),
                const SizedBox(height: 4),
                Text(formatIsoDate(appointment.startAt)),
                const SizedBox(height: 4),
                Text('Duree ${appointment.prestation.durationMinutes} min'),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
