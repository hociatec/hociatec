import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/auth/data/auth_repository.dart';
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

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(title: const Text('À propos')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: <Widget>[
            Text(
              'À propos',
              style: theme.textTheme.headlineMedium?.copyWith(
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              'Retrouvez les informations sur l’application et l’accès à votre session client.',
              style: theme.textTheme.bodyLarge?.copyWith(
                color: colorScheme.onSurfaceVariant,
                height: 1.45,
              ),
            ),
            const SizedBox(height: 24),
            const Card(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: _AuthSessionCard(),
              ),
            ),
            const SizedBox(height: 20),
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
                          'La source AltStore officielle reste disponible pour suivre les mises à jour de l’application.',
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
                        const SizedBox(height: 12),
                        OutlinedButton.icon(
                          onPressed: () => context.push('/contact'),
                          icon: const Icon(Icons.mail_outline),
                          label: const Text('Ouvrir le contact'),
                        ),
                      ],
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
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
          content: Text(
            'Impossible d’ouvrir AltStore ou la source de mise à jour.',
          ),
        ),
      );
    }
  }
}

class _AuthSessionCard extends ConsumerWidget {
  const _AuthSessionCard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final userAsync = ref.watch(currentAuthUserProvider);

    return userAsync.when(
      data: (user) {
        if (user == null) {
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Text(
                'Connexion',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Aucune session active. Connectez-vous pour réserver un rendez-vous et demander un audit.',
              ),
              const SizedBox(height: 18),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: <Widget>[
                  FilledButton(
                    onPressed: () => context.push('/connexion'),
                    child: const Text('Se connecter'),
                  ),
                  OutlinedButton(
                    onPressed: () => context.push('/compte'),
                    child: const Text('Mon compte'),
                  ),
                ],
              ),
            ],
          );
        }

        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'Session active',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
            ),
            const SizedBox(height: 8),
            Text('${user.displayName}\n${user.email}'),
            const SizedBox(height: 18),
            Wrap(
              spacing: 12,
              runSpacing: 12,
              children: <Widget>[
                FilledButton(
                  onPressed: () => context.push('/compte'),
                  child: const Text('Mon compte'),
                ),
                OutlinedButton(
                  onPressed: () async {
                    await ref.read(authRepositoryProvider).logout();
                    ref.invalidate(currentAuthUserProvider);
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Session déconnectée.')),
                      );
                    }
                  },
                  child: const Text('Se déconnecter'),
                ),
              ],
            ),
          ],
        );
      },
      error: (error, stackTrace) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            'Connexion',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
          ),
          const SizedBox(height: 8),
          Text(
            resolveApiErrorMessage(error, 'Impossible de vérifier la session.'),
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: <Widget>[
              FilledButton(
                onPressed: () => context.push('/connexion'),
                child: const Text('Se connecter'),
              ),
              OutlinedButton(
                onPressed: () => context.push('/compte'),
                child: const Text('Mon compte'),
              ),
            ],
          ),
        ],
      ),
      loading: () => const Center(child: CircularProgressIndicator()),
    );
  }
}
