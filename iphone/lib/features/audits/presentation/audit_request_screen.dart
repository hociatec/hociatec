import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/audits/data/audit_repository.dart';
import 'package:hociatec_mobile/shared/utils/api_error_message.dart';

class AuditRequestScreen extends ConsumerStatefulWidget {
  const AuditRequestScreen({super.key});

  @override
  ConsumerState<AuditRequestScreen> createState() => _AuditRequestScreenState();
}

class _AuditRequestScreenState extends ConsumerState<AuditRequestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _urlController = TextEditingController();
  final _objectivesController = TextEditingController();
  String? _type;
  bool _submitting = false;

  @override
  void dispose() {
    _urlController.dispose();
    _objectivesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final typesAsync = ref.watch(auditTypesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Demander un audit')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: <Widget>[
            Text(
              'Demande d audit',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
            ),
            const SizedBox(height: 10),
            const Text(
              'La demande est envoyee a l API audit. Comme pour le rendez-vous, cet endpoint exige un compte client connecte.',
            ),
            const SizedBox(height: 20),
            typesAsync.when(
              data: (types) => DropdownButtonFormField<String>(
                initialValue: _type,
                decoration: const InputDecoration(labelText: 'Type d audit'),
                items: types
                    .map(
                      (item) => DropdownMenuItem<String>(
                        value: item.value,
                        child: Text(item.label),
                      ),
                    )
                    .toList(growable: false),
                onChanged: (value) => setState(() => _type = value),
                validator: (value) => value == null || value.isEmpty
                    ? 'Choisissez un type.'
                    : null,
              ),
              error: (error, stackTrace) => Text(
                resolveApiErrorMessage(
                    error, 'Impossible de charger les types d audit.'),
              ),
              loading: () => const Center(child: CircularProgressIndicator()),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _urlController,
              keyboardType: TextInputType.url,
              decoration: const InputDecoration(labelText: 'URL a auditer'),
              validator: (value) {
                final text = value?.trim() ?? '';
                if (text.isEmpty || !text.startsWith('http')) {
                  return 'Saisissez une URL valide.';
                }
                return null;
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _objectivesController,
              minLines: 4,
              maxLines: 6,
              decoration: const InputDecoration(
                labelText: 'Objectifs',
                alignLabelWithHint: true,
              ),
            ),
            const SizedBox(height: 18),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: Text(_submitting ? 'Envoi...' : 'Envoyer la demande'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _type == null) {
      return;
    }

    setState(() => _submitting = true);

    try {
      final message = await ref.read(auditRepositoryProvider).createAudit(
            type: _type!,
            url: _urlController.text.trim(),
            objectives: _objectivesController.text.trim().isEmpty
                ? null
                : _objectivesController.text.trim(),
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
                error, 'Impossible d envoyer la demande d audit.'),
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
