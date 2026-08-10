import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/trade_ins/data/trade_in_repository.dart';
import 'package:hociatec_mobile/shared/utils/api_error_message.dart';

class TradeInRequestScreen extends ConsumerStatefulWidget {
  const TradeInRequestScreen({super.key});

  @override
  ConsumerState<TradeInRequestScreen> createState() =>
      _TradeInRequestScreenState();
}

class _TradeInRequestScreenState extends ConsumerState<TradeInRequestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _productNameController = TextEditingController();
  final _purchasePriceController = TextEditingController();
  final _purchaseYearController = TextEditingController();
  final _brandController = TextEditingController();
  final _modelController = TextEditingController();
  final _serialNumberController = TextEditingController();
  final _descriptionController = TextEditingController();
  String? _category;
  String? _conditionGrade;
  bool _functional = true;
  bool _hasAccessories = false;
  bool _hasProofOfPurchase = false;
  bool _consent = false;
  bool _submitting = false;
  PlatformFile? _ribFile;

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _productNameController.dispose();
    _purchasePriceController.dispose();
    _purchaseYearController.dispose();
    _brandController.dispose();
    _modelController.dispose();
    _serialNumberController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final metadataAsync = ref.watch(tradeInMetadataProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Reprise')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: <Widget>[
            Text(
              'Demande de reprise',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
            ),
            const SizedBox(height: 10),
            const Text(
              'Ce formulaire envoie une vraie demande a l API publique de reprise avec le RIB en piece jointe.',
            ),
            const SizedBox(height: 20),
            metadataAsync.when(
              data: (metadata) => Column(
                children: <Widget>[
                  DropdownButtonFormField<String>(
                    initialValue: _category,
                    decoration: const InputDecoration(labelText: 'Categorie'),
                    items: metadata.categories
                        .map(
                          (item) => DropdownMenuItem<String>(
                            value: item.value,
                            child: Text(item.label),
                          ),
                        )
                        .toList(growable: false),
                    onChanged: (value) => setState(() => _category = value),
                    validator: (value) => value == null || value.isEmpty
                        ? 'Choisissez une categorie.'
                        : null,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: _conditionGrade,
                    decoration: const InputDecoration(labelText: 'Etat'),
                    items: metadata.conditions
                        .map(
                          (item) => DropdownMenuItem<String>(
                            value: item.value,
                            child: Text(item.label),
                          ),
                        )
                        .toList(growable: false),
                    onChanged: (value) =>
                        setState(() => _conditionGrade = value),
                    validator: (value) => value == null || value.isEmpty
                        ? 'Choisissez un etat.'
                        : null,
                  ),
                ],
              ),
              error: (error, stackTrace) => Text(
                resolveApiErrorMessage(
                    error, 'Impossible de charger les options de reprise.'),
              ),
              loading: () => const Center(child: CircularProgressIndicator()),
            ),
            const SizedBox(height: 12),
            _buildTextField(_firstNameController, 'Prenom'),
            const SizedBox(height: 12),
            _buildTextField(_lastNameController, 'Nom'),
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
            _buildTextField(_phoneController, 'Telephone'),
            const SizedBox(height: 12),
            _buildTextField(_productNameController, 'Nom du produit'),
            const SizedBox(height: 12),
            _buildTextField(_brandController, 'Marque'),
            const SizedBox(height: 12),
            _buildTextField(_modelController, 'Modele'),
            const SizedBox(height: 12),
            _buildTextField(_serialNumberController, 'Numero de serie'),
            const SizedBox(height: 12),
            TextFormField(
              controller: _purchasePriceController,
              keyboardType: TextInputType.number,
              decoration:
                  const InputDecoration(labelText: 'Prix d achat en euros'),
              validator: _requiredValidator,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _purchaseYearController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Annee d achat'),
              validator: (value) {
                final year = int.tryParse(value?.trim() ?? '');
                final currentYear = DateTime.now().year;
                if (year == null || year < 2000 || year > currentYear) {
                  return 'Saisissez une annee valide, au plus tard $currentYear.';
                }
                return null;
              },
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: _descriptionController,
              minLines: 4,
              maxLines: 6,
              decoration: const InputDecoration(
                labelText: 'Description',
                alignLabelWithHint: true,
              ),
              validator: (value) => (value?.trim().length ?? 0) < 10
                  ? 'Ajoutez plus de details.'
                  : null,
            ),
            const SizedBox(height: 12),
            SwitchListTile(
              value: _functional,
              title: const Text('Produit fonctionnel'),
              onChanged: (value) => setState(() => _functional = value),
            ),
            CheckboxListTile(
              value: _hasAccessories,
              title: const Text('Accessoires disponibles'),
              onChanged: (value) =>
                  setState(() => _hasAccessories = value ?? false),
            ),
            CheckboxListTile(
              value: _hasProofOfPurchase,
              title: const Text('Preuve d achat disponible'),
              onChanged: (value) =>
                  setState(() => _hasProofOfPurchase = value ?? false),
            ),
            CheckboxListTile(
              value: _consent,
              title: const Text('J accepte le traitement de ma demande'),
              onChanged: (value) => setState(() => _consent = value ?? false),
            ),
            OutlinedButton.icon(
              onPressed: _pickPdf,
              icon: const Icon(Icons.attach_file),
              label: Text(
                _ribFile == null
                    ? 'Ajouter le RIB PDF'
                    : 'RIB: ${_ribFile!.name}',
              ),
            ),
            const SizedBox(height: 18),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              child: Text(_submitting ? 'Envoi...' : 'Envoyer la reprise'),
            ),
          ],
        ),
      ),
    );
  }

  TextFormField _buildTextField(
    TextEditingController controller,
    String label,
  ) {
    return TextFormField(
      controller: controller,
      decoration: InputDecoration(labelText: label),
      validator: _requiredValidator,
    );
  }

  String? _requiredValidator(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Champ requis.';
    }
    return null;
  }

  Future<void> _pickPdf() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: const <String>['pdf'],
      withData: true,
    );

    if (result == null || result.files.isEmpty) {
      return;
    }

    setState(() => _ribFile = result.files.single);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    if (!_consent) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Le consentement est requis.')),
      );
      return;
    }

    if (_ribFile == null || _ribFile!.bytes == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez ajouter un RIB PDF.')),
      );
      return;
    }

    setState(() => _submitting = true);

    try {
      final price = double.tryParse(
            _purchasePriceController.text.trim().replaceAll(',', '.'),
          ) ??
          0;

      final message = await ref.read(tradeInRepositoryProvider).create(
            firstName: _firstNameController.text.trim(),
            lastName: _lastNameController.text.trim(),
            email: _emailController.text.trim(),
            phone: _phoneController.text.trim(),
            category: _category!,
            productName: _productNameController.text.trim(),
            purchasePriceCents: (price * 100).round(),
            purchaseYear: int.parse(_purchaseYearController.text.trim()),
            brand: _brandController.text.trim(),
            model: _modelController.text.trim(),
            serialNumber: _serialNumberController.text.trim(),
            conditionGrade: _conditionGrade!,
            functional: _functional,
            hasAccessories: _hasAccessories,
            hasProofOfPurchase: _hasProofOfPurchase,
            description: _descriptionController.text.trim(),
            consent: _consent,
            rib: _ribFile!,
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
                error, 'Impossible d envoyer votre reprise.'),
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
