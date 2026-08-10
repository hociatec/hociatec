import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/appointments/data/appointment_repository.dart';
import 'package:hociatec_mobile/shared/utils/api_error_message.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class AppointmentRequestScreen extends ConsumerStatefulWidget {
  const AppointmentRequestScreen({super.key});

  @override
  ConsumerState<AppointmentRequestScreen> createState() =>
      _AppointmentRequestScreenState();
}

class _AppointmentRequestScreenState
    extends ConsumerState<AppointmentRequestScreen> {
  AppointmentPrestation? _selectedPrestation;
  AppointmentSlot? _selectedSlot;
  bool _loadingSlots = false;
  bool _submitting = false;
  List<AppointmentSlot> _slots = const <AppointmentSlot>[];

  @override
  Widget build(BuildContext context) {
    final prestationsAsync = ref.watch(publicAppointmentPrestationsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Prendre rendez-vous')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: <Widget>[
          Text(
            'Reservation de rendez-vous',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 10),
          const Text(
            'Les creneaux sont charges depuis l API publique. La creation finale du rendez-vous passe par l API client et necessite une connexion.',
          ),
          const SizedBox(height: 20),
          prestationsAsync.when(
            data: (prestations) =>
                DropdownButtonFormField<AppointmentPrestation>(
              initialValue: _selectedPrestation,
              decoration: const InputDecoration(labelText: 'Prestation'),
              items: prestations
                  .map(
                    (item) => DropdownMenuItem<AppointmentPrestation>(
                      value: item,
                      child: Text(item.name),
                    ),
                  )
                  .toList(growable: false),
              onChanged: (value) async {
                setState(() {
                  _selectedPrestation = value;
                  _selectedSlot = null;
                  _slots = const <AppointmentSlot>[];
                });
                await _loadSlots();
              },
            ),
            error: (error, stackTrace) => Text(
              resolveApiErrorMessage(
                  error, 'Impossible de charger les prestations.'),
            ),
            loading: () => const Center(child: CircularProgressIndicator()),
          ),
          const SizedBox(height: 20),
          if (_loadingSlots) const Center(child: CircularProgressIndicator()),
          if (!_loadingSlots && _slots.isNotEmpty)
            ..._slots.map(
              (slot) {
                final start = DateTime.parse(slot.start).toLocal();
                final label =
                    '${start.hour.toString().padLeft(2, '0')}:${start.minute.toString().padLeft(2, '0')}';

                return Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: ChoiceChip(
                    label: Text('${formatIsoDate(slot.start)} a $label'),
                    selected: _selectedSlot == slot,
                    onSelected: (_) => setState(() => _selectedSlot = slot),
                  ),
                );
              },
            ),
          if (!_loadingSlots && _selectedPrestation != null && _slots.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 12),
              child:
                  Text('Aucun creneau disponible sur les 14 prochains jours.'),
            ),
          const SizedBox(height: 20),
          FilledButton(
            onPressed: _selectedPrestation == null ||
                    _selectedSlot == null ||
                    _submitting
                ? null
                : _submit,
            child: Text(
                _submitting ? 'Reservation...' : 'Confirmer le rendez-vous'),
          ),
        ],
      ),
    );
  }

  Future<void> _loadSlots() async {
    final prestation = _selectedPrestation;
    if (prestation == null) {
      return;
    }

    setState(() => _loadingSlots = true);

    try {
      final now = DateTime.now().toUtc();
      final slots =
          await ref.read(appointmentRepositoryProvider).fetchAvailability(
                prestationId: prestation.id,
                start: now,
                end: now.add(const Duration(days: 14)),
              );
      if (!mounted) {
        return;
      }
      setState(() => _slots = slots.take(8).toList(growable: false));
    } catch (error) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            resolveApiErrorMessage(
                error, 'Impossible de charger les creneaux.'),
          ),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _loadingSlots = false);
      }
    }
  }

  Future<void> _submit() async {
    final prestation = _selectedPrestation;
    final slot = _selectedSlot;
    if (prestation == null || slot == null) {
      return;
    }

    setState(() => _submitting = true);

    try {
      final message = await ref.read(appointmentRepositoryProvider).book(
            prestationId: prestation.id,
            startAt: slot.start,
          );
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message)),
      );
    } catch (error) {
      if (!mounted) {
        return;
      }
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            resolveApiErrorMessage(
              error,
              'Impossible de reserver ce rendez-vous.',
            ),
          ),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }
}
