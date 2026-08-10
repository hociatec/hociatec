import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/quotes/data/quote_repository.dart';
import 'package:hociatec_mobile/shared/utils/api_error_message.dart';

class QuoteRequestScreen extends ConsumerStatefulWidget {
  const QuoteRequestScreen({super.key});

  @override
  ConsumerState<QuoteRequestScreen> createState() => _QuoteRequestScreenState();
}

class _QuoteRequestScreenState extends ConsumerState<QuoteRequestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _companyController = TextEditingController();
  final _addressController = TextEditingController();
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();
  int? _selectedServiceId;
  bool _isSubmitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _companyController.dispose();
    _addressController.dispose();
    _titleController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final servicesAsync = ref.watch(publicQuoteServicesProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Creer un devis')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: <Widget>[
            Text(
              'Votre demande de devis',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
            ),
            const SizedBox(height: 10),
            const Text(
              'Remplissez les informations utiles. Le formulaire est envoye a l API publique des devis.',
            ),
            const SizedBox(height: 20),
            TextFormField(
              controller: _nameController,
              decoration: const InputDecoration(labelText: 'Nom'),
              validator: _requiredValidator,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _emailController,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(labelText: 'Email'),
              validator: (value) {
                final text = value?.trim() ?? '';
                if (text.isEmpty || !text.contains('@')) {
                  return 'Veuillez saisir un email valide.';
                }
                return null;
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _companyController,
              decoration: const InputDecoration(labelText: 'Societe'),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _addressController,
              decoration: const InputDecoration(labelText: 'Adresse'),
            ),
            const SizedBox(height: 12),
            servicesAsync.when(
              data: (services) => DropdownButtonFormField<int?>(
                initialValue: _selectedServiceId,
                decoration: const InputDecoration(labelText: 'Service associe'),
                items: <DropdownMenuItem<int?>>[
                  const DropdownMenuItem<int?>(
                    value: null,
                    child: Text('Aucun service specifique'),
                  ),
                  ...services.map(
                    (service) => DropdownMenuItem<int?>(
                      value: service.id,
                      child: Text(service.title),
                    ),
                  ),
                ],
                onChanged: (value) =>
                    setState(() => _selectedServiceId = value),
              ),
              error: (error, stackTrace) => Text(
                resolveApiErrorMessage(
                  error,
                  'Impossible de charger les services du devis.',
                ),
              ),
              loading: () => const Padding(
                padding: EdgeInsets.symmetric(vertical: 16),
                child: Center(child: CircularProgressIndicator()),
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _titleController,
              decoration: const InputDecoration(labelText: 'Objet du devis'),
              validator: _requiredValidator,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _descriptionController,
              minLines: 4,
              maxLines: 6,
              decoration: const InputDecoration(
                labelText: 'Details de la demande',
                alignLabelWithHint: true,
              ),
              validator: (value) {
                if ((value?.trim().length ?? 0) < 10) {
                  return 'Ajoutez un peu plus de details.';
                }
                return null;
              },
            ),
            const SizedBox(height: 18),
            FilledButton(
              onPressed: _isSubmitting ? null : _submit,
              child: Text(_isSubmitting ? 'Envoi...' : 'Envoyer le devis'),
            ),
          ],
        ),
      ),
    );
  }

  String? _requiredValidator(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Champ requis.';
    }
    return null;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final message = await ref.read(quoteRepositoryProvider).createQuote(
            customerName: _nameController.text.trim(),
            customerEmail: _emailController.text.trim(),
            customerCompany: _companyController.text.trim().isEmpty
                ? null
                : _companyController.text.trim(),
            customerAddress: _addressController.text.trim().isEmpty
                ? null
                : _addressController.text.trim(),
            requestTitle: _titleController.text.trim(),
            requestDescription: _descriptionController.text.trim(),
            selectedServiceId: _selectedServiceId,
          );

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message)),
      );
      _formKey.currentState!.reset();
      _nameController.clear();
      _emailController.clear();
      _companyController.clear();
      _addressController.clear();
      _titleController.clear();
      _descriptionController.clear();
      setState(() => _selectedServiceId = null);
    } catch (error) {
      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            resolveApiErrorMessage(error, 'Impossible d envoyer votre devis.'),
          ),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }
}
