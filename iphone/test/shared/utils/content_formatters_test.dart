import 'package:flutter_test/flutter_test.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

void main() {
  group('formatPriceCents', () {
    test('formats euros in french locale', () {
      expect(formatPriceCents(91000), '910,00 €');
    });

    test('appends suffix when provided', () {
      expect(formatPriceCents(120000, suffix: 'HT'), '1 200,00 € HT');
    });
  });

  group('formatServiceBillingMode', () {
    test('maps known billing labels', () {
      expect(formatServiceBillingMode('prix fixe'), 'Prix fixe');
      expect(formatServiceBillingMode('intervention'), 'Par intervention');
      expect(formatServiceBillingMode('jour'), 'À la journée');
    });

    test('falls back to input or default', () {
      expect(formatServiceBillingMode('Forfait premium'), 'Forfait premium');
      expect(formatServiceBillingMode(null), 'Prix fixe');
    });
  });

  group('text helpers', () {
    test('extracts first sentence from html content', () {
      const input = '<p>Premiere phrase. Deuxieme phrase.</p>';
      expect(extractFirstSentence(input, 'fallback'), 'Premiere phrase.');
    });

    test('strips basic html entities and tags', () {
      const input = '<p>Bonjour&nbsp;&amp; bienvenue</p>';
      expect(stripBasicHtml(input), 'Bonjour & bienvenue');
    });
  });
}
