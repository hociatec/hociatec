import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/catalog/application/product_share_service.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';

class ProductEmailShareSheet extends ConsumerStatefulWidget {
  const ProductEmailShareSheet({
    required this.product,
    super.key,
  });

  final CatalogProduct product;

  @override
  ConsumerState<ProductEmailShareSheet> createState() => _ProductEmailShareSheetState();
}

class _ProductEmailShareSheetState extends ConsumerState<ProductEmailShareSheet> {
  final TextEditingController _emailController = TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 20,
          bottom: 20 + MediaQuery.of(context).viewInsets.bottom,
        ),
        child: Form(
          key: _formKey,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'Partager ${widget.product.displayName}',
                style: theme.textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                'Renseignez une adresse e-mail. Le bouton enverra le produit via l API publique ou ouvrira votre messagerie si necessaire.',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 18),
              TextFormField(
                controller: _emailController,
                keyboardType: TextInputType.emailAddress,
                autofillHints: const <String>[AutofillHints.email],
                decoration: const InputDecoration(
                  labelText: 'Adresse e-mail du destinataire',
                  hintText: 'ami@exemple.com',
                ),
                validator: (value) {
                  return ref
                      .read(productShareServiceProvider)
                      .validateRecipientEmail(value ?? '');
                },
              ),
              const SizedBox(height: 18),
              Row(
                children: <Widget>[
                  Expanded(
                    child: OutlinedButton(
                      onPressed: _isSubmitting ? null : () => Navigator.of(context).pop(),
                      child: const Text('Annuler'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton(
                      onPressed: _isSubmitting ? null : _submit,
                      child: Text(_isSubmitting ? 'Envoi...' : 'Envoyer par e-mail'),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    final email = _emailController.text.trim();
    final messenger = ScaffoldMessenger.of(context);
    final shareService = ref.read(productShareServiceProvider);

    setState(() => _isSubmitting = true);

    try {
      final result = await shareService.shareByEmail(
        product: widget.product,
        email: email,
      );

      if (!mounted) return;
      if (result.isSuccess) {
        Navigator.of(context).pop();
      }
      messenger.showSnackBar(SnackBar(content: Text(result.message)));
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }
}
