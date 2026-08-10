import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';

const _iosDownloadUri = 'https://github.com/hociatec/hociatec-downloads/releases/download/ios-latest/hociatec-altstore-latest.ipa';

class AboutScreen extends StatefulWidget {
  const AboutScreen({super.key});

  @override
  State<AboutScreen> createState() => _AboutScreenState();
}

class _AboutScreenState extends State<AboutScreen> {
  late final Future<PackageInfo> _packageInfo = PackageInfo.fromPlatform();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'A propos',
              style: theme.textTheme.headlineMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 24),
            Expanded(
              child: Card(
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
                              fontWeight: FontWeight.w700,
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
                            'Accedez directement au dernier lien de telechargement pour mettre a jour l’application via AltStore.',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: colorScheme.onSurfaceVariant,
                              height: 1.5,
                            ),
                          ),
                          const Spacer(),
                          FilledButton.icon(
                            onPressed: _openUpdateLink,
                            icon: const Icon(Icons.system_update_alt),
                            label: const Text('Mettre a jour'),
                          ),
                        ],
                      );
                    },
                  ),
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
    final uri = Uri.parse(_iosDownloadUri);

    if (!await launchUrl(uri, mode: LaunchMode.externalApplication) && mounted) {
      messenger.showSnackBar(
        const SnackBar(
          content: Text('Impossible d’ouvrir le lien de mise a jour.'),
        ),
      );
    }
  }
}
