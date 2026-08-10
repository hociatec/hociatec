import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/appointments/data/appointment_repository.dart';
import 'package:hociatec_mobile/features/auth/data/auth_repository.dart';
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
    final userAsync = ref.watch(currentAuthUserProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Prendre rendez-vous')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: <Widget>[
          Text(
            'Réservation de rendez-vous',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 10),
          const Text(
            'Les créneaux sont chargés depuis l’API publique. La création finale du rendez-vous nécessite une connexion.',
          ),
          const SizedBox(height: 16),
          userAsync.when(
            data: (user) {
              if (user != null) {
                return const SizedBox.shrink();
              }
              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      const Text(
                        'Connexion requise pour confirmer le rendez-vous.',
                      ),
                      const SizedBox(height: 12),
                      FilledButton(
                        onPressed: () => context.push('/connexion'),
                        child: const Text('Se connecter'),
                      ),
                    ],
                  ),
                ),
              );
            },
            error: (_, __) => const SizedBox.shrink(),
            loading: () => const SizedBox.shrink(),
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
                error,
                'Impossible de charger les prestations.',
              ),
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
                    label: Text('${formatIsoDate(slot.start)} à $label'),
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
                  Text('Aucun créneau disponible sur les 14 prochains jours.'),
            ),
          const SizedBox(height: 20),
          FilledButton(
            onPressed: userAsync.valueOrNull == null ||
                    _selectedPrestation == null ||
                    _selectedSlot == null ||
                    _submitting
                ? null
                : _submit,
            child: Text(
              _submitting ? 'Réservation...' : 'Confirmer le rendez-vous',
            ),
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
              error,
              'Impossible de charger les créneaux.',
            ),
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
    final user = ref.read(currentAuthUserProvider).valueOrNull;

    if (user == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Connectez-vous avant de réserver un rendez-vous.'),
        ),
      );
      return;
    }

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
              'Impossible de réserver ce rendez-vous.',
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
