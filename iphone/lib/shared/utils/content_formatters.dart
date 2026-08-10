import 'package:intl/intl.dart';

String formatPriceCents(int value, {String? suffix}) {
  final formatter = NumberFormat.currency(
    locale: 'fr_FR',
    symbol: '€',
    decimalDigits: 2,
  );
  final formatted = formatter.format(value / 100);

  if (suffix == null || suffix.isEmpty) {
    return formatted;
  }

  return '$formatted $suffix';
}

String formatServiceBillingMode(String? value) {
  final normalized = value?.trim().toLowerCase() ?? '';

  switch (normalized) {
    case '':
    case 'prix fixe':
      return 'Prix fixe';
    case 'heure':
    case 'horaire':
      return 'Horaire';
    case 'jour':
      return 'À la journée';
    case 'intervention':
      return 'Par intervention';
    case 'audit':
      return 'Audit';
    case 'installation':
      return 'Installation';
    case 'maintenance':
      return 'Maintenance';
    default:
      return value?.trim().isNotEmpty == true ? value!.trim() : 'Prix fixe';
  }
}

String extractFirstSentence(String? value, String fallback) {
  final normalized = stripBasicHtml(value).trim();
  if (normalized.isEmpty) return fallback;

  final match = RegExp(r'[^.!?]+[.!?]?').firstMatch(normalized);
  return (match?.group(0) ?? normalized).trim();
}

String stripBasicHtml(String? value) {
  if (value == null || value.trim().isEmpty) return '';

  return value
      .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), '\n')
      .replaceAll(RegExp(r'</p>', caseSensitive: false), '\n\n')
      .replaceAll(RegExp(r'<[^>]*>'), '')
      .replaceAll('&nbsp;', ' ')
      .replaceAll('&amp;', '&')
      .replaceAll('&quot;', '"')
      .replaceAll('&#39;', "'")
      .trim();
}

String formatIsoDate(String? value) {
  if (value == null || value.isEmpty) return 'Date non definie';

  final date = DateTime.tryParse(value)?.toLocal();
  if (date == null) return value;

  const months = <String>[
    'janv.',
    'fevr.',
    'mars',
    'avr.',
    'mai',
    'juin',
    'juil.',
    'aout',
    'sept.',
    'oct.',
    'nov.',
    'dec.',
  ];

  return '${date.day} ${months[date.month - 1]} ${date.year}';
}
