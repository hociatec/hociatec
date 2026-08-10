import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/home_cards.dart';
import 'package:hociatec_mobile/features/services/data/services_repository.dart';

class ServicesScreen extends ConsumerWidget {
  const ServicesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final servicesAsync = ref.watch(allServicesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Prestations')),
      body: servicesAsync.when(
        data: (services) => ListView.separated(
          padding: const EdgeInsets.all(20),
          itemBuilder: (context, index) => HomeServiceCard(service: services[index]),
          separatorBuilder: (context, index) => const SizedBox(height: 14),
          itemCount: services.length,
        ),
        error: (error, stackTrace) => Center(child: Text(error.toString())),
        loading: () => const Center(child: CircularProgressIndicator()),
      ),
    );
  }
}
