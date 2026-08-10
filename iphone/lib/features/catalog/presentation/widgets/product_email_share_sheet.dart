import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/catalog/data/catalog_repository.dart';
import 'package:hociatec_mobile/features/catalog/domain/catalog_product.dart';
import 'package:hociatec_mobile/shared/utils/public_urls.dart';
import 'package:url_launcher/url_launcher.dart';

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
                  final normalized = value?.trim() ?? '';
                  if (normalized.isEmpty) {
                    return 'Veuillez renseigner une adresse e-mail.';
                  }
                  final emailPattern = RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$');
                  if (!emailPattern.hasMatch(normalized)) {
                    return 'Cette adresse e-mail ne semble pas valide.';
                  }
                  return null;
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
    final siteBaseUrl = ref.read(siteBaseUrlProvider);
    final absoluteUrl = productPublicUrl(siteBaseUrl, widget.product.slug);
    final mailto = mailtoUri(
      email: email,
      subject: 'Decouvrir : ${widget.product.displayName}',
      body: [
        'Bonjour,',
        '',
        'Je te partage ce produit : ${widget.product.displayName}',
        '',
        'Lien direct : $absoluteUrl',
        '',
        widget.product.shortDescription ??
            'Consulte la fiche produit pour obtenir tous les details.',
      ].join('\n'),
    );

    setState(() => _isSubmitting = true);

    try {
      await ref.read(catalogRepositoryProvider).shareProductByEmail(
            slug: widget.product.slug,
            email: email,
          );

      if (!mounted) return;
      Navigator.of(context).pop();
      messenger.showSnackBar(
        const SnackBar(content: Text('Le produit a ete envoye par e-mail.')),
      );
    } on DioException catch (error) {
      if (error.response?.statusCode == 503) {
        final opened = await launchUrl(mailto, mode: LaunchMode.externalApplication);
        if (!mounted) return;
        Navigator.of(context).pop();
        messenger.showSnackBar(
          SnackBar(
            content: Text(
              opened
                  ? 'Le service e-mail est indisponible. Votre messagerie a ete ouverte.'
                  : 'Le service e-mail est indisponible et la messagerie n a pas pu etre ouverte.',
            ),
          ),
        );
        return;
      }

      messenger.showSnackBar(
        SnackBar(
          content: Text(
            (error.response?.data is Map<String, dynamic>)
                ? (((error.response!.data as Map<String, dynamic>)['message'] as String?) ??
                    "Impossible d'envoyer le produit par e-mail.")
                : "Impossible d'envoyer le produit par e-mail.",
          ),
        ),
      );
    } catch (_) {
      messenger.showSnackBar(
        const SnackBar(content: Text("Impossible d'envoyer le produit par e-mail.")),
      );
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }
}
