import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/contact/data/contact_repository.dart';
import 'package:hociatec_mobile/shared/utils/api_error_message.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';

const _altStoreSourceUri =
    'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-source.json';
const _altStoreDeepLink =
    'altstore://source?url=https%3A%2F%2Fgithub.com%2Fhociatec%2Fhociatec-downloads%2Freleases%2Fdownload%2Fios-latest%2Fhociatec-altstore-source.json';

class AboutScreen extends ConsumerStatefulWidget {
  const AboutScreen({super.key});

  @override
  ConsumerState<AboutScreen> createState() => _AboutScreenState();
}

class _AboutScreenState extends ConsumerState<AboutScreen> {
  late final Future<PackageInfo> _packageInfo = PackageInfo.fromPlatform();
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _subjectController = TextEditingController();
  final _messageController = TextEditingController();
  bool _isSubmitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _subjectController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text('A propos')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: <Widget>[
            Text(
              'A propos',
              style: theme.textTheme.headlineMedium?.copyWith(
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              'Retrouvez les informations sur l application et contactez directement Hociatec depuis ce formulaire.',
              style: theme.textTheme.bodyLarge?.copyWith(
                color: colorScheme.onSurfaceVariant,
                height: 1.45,
              ),
            ),
            const SizedBox(height: 24),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: FutureBuilder<PackageInfo>(
                  future: _packageInfo,
                  builder: (context, snapshot) {
                    final packageInfo = snapshot.data;
                    final version = packageInfo == null
                        ? 'Chargement...'
                        : '${packageInfo.version} (${packageInfo.buildNumber})';

                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Container(
                          width: 64,
                          height: 64,
                          decoration: BoxDecoration(
                            color: colorScheme.primary.withValues(alpha: 0.10),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Icon(
                            Icons.info_outline,
                            color: colorScheme.primary,
                            size: 30,
                          ),
                        ),
                        const SizedBox(height: 24),
                        Text(
                          'Hociatec',
                          style: theme.textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          'Version actuelle : $version',
                          style: theme.textTheme.bodyLarge?.copyWith(
                            color: colorScheme.onSurfaceVariant,
                            height: 1.5,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'La source AltStore officielle reste disponible pour suivre les mises a jour de l application.',
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: colorScheme.onSurfaceVariant,
                            height: 1.5,
                          ),
                        ),
                        const SizedBox(height: 20),
                        FilledButton.icon(
                          onPressed: _openUpdateLink,
                          icon: const Icon(Icons.system_update_alt),
                          label: const Text('Ouvrir dans AltStore'),
                        ),
                      ],
                    );
                  },
                ),
              ),
            ),
            const SizedBox(height: 20),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        'Contact',
                        style: theme.textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Envoyez votre demande via le formulaire ci-dessous.',
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: colorScheme.onSurfaceVariant,
                        ),
                      ),
                      const SizedBox(height: 18),
                      TextFormField(
                        controller: _nameController,
                        textInputAction: TextInputAction.next,
                        decoration: const InputDecoration(
                          labelText: 'Nom',
                          prefixIcon: Icon(Icons.person_outline),
                        ),
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Veuillez saisir votre nom.';
                          }

                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _emailController,
                        textInputAction: TextInputAction.next,
                        keyboardType: TextInputType.emailAddress,
                        decoration: const InputDecoration(
                          labelText: 'Email',
                          prefixIcon: Icon(Icons.alternate_email),
                        ),
                        validator: (value) {
                          final email = value?.trim() ?? '';
                          if (email.isEmpty || !email.contains('@')) {
                            return 'Veuillez saisir un email valide.';
                          }

                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _subjectController,
                        textInputAction: TextInputAction.next,
                        decoration: const InputDecoration(
                          labelText: 'Sujet',
                          prefixIcon: Icon(Icons.label_outline),
                        ),
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Veuillez saisir un sujet.';
                          }

                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _messageController,
                        minLines: 5,
                        maxLines: 7,
                        decoration: const InputDecoration(
                          labelText: 'Message',
                          alignLabelWithHint: true,
                          prefixIcon: Padding(
                            padding: EdgeInsets.only(bottom: 80),
                            child: Icon(Icons.message_outlined),
                          ),
                        ),
                        validator: (value) {
                          if (value == null || value.trim().length < 10) {
                            return 'Votre message doit contenir au moins 10 caracteres.';
                          }

                          return null;
                        },
                      ),
                      const SizedBox(height: 18),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton(
                          onPressed: _isSubmitting ? null : _submitContactForm,
                          child: Text(
                            _isSubmitting ? 'Envoi...' : 'Envoyer la demande',
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _submitContactForm() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final message = await ref.read(contactRepositoryProvider).submit(
            name: _nameController.text.trim(),
            email: _emailController.text.trim(),
            subject: _subjectController.text.trim(),
            message: _messageController.text.trim(),
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
      _subjectController.clear();
      _messageController.clear();
    } catch (error) {
      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            resolveApiErrorMessage(
                error, 'Impossible d envoyer votre message.'),
          ),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }

  Future<void> _openUpdateLink() async {
    final messenger = ScaffoldMessenger.of(context);
    final altStoreUri = Uri.parse(_altStoreDeepLink);
    final sourceUri = Uri.parse(_altStoreSourceUri);

    final openedAltStore = await launchUrl(
      altStoreUri,
      mode: LaunchMode.externalApplication,
    );

    if (openedAltStore || !mounted) {
      return;
    }

    final openedSource = await launchUrl(
      sourceUri,
      mode: LaunchMode.externalApplication,
    );

    if (!openedSource && mounted) {
      messenger.showSnackBar(
        const SnackBar(
          content:
              Text('Impossible d ouvrir AltStore ou la source de mise a jour.'),
        ),
      );
    }
  }
}
