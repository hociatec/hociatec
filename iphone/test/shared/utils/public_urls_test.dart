import 'package:flutter_test/flutter_test.dart';
import 'package:hociatec_mobile/shared/utils/public_urls.dart';

void main() {
  test('builds product public url', () {
    expect(
      productPublicUrl('https://hociatec.fr', 'iphone-16-pro'),
      'https://hociatec.fr/catalogue/produits/iphone-16-pro',
    );
  });

  test('builds news public url', () {
    expect(
      newsPublicUrl('https://hociatec.fr', 'nouvelle-actualite'),
      'https://hociatec.fr/actualites/nouvelle-actualite',
    );
  });

  test('builds facebook share uri', () {
    expect(
      facebookShareUri('https://hociatec.fr/catalogue/produits/iphone-16-pro').toString(),
      'https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fhociatec.fr%2Fcatalogue%2Fproduits%2Fiphone-16-pro',
    );
  });

  test('builds mailto uri', () {
    expect(
      mailtoUri(
        email: 'client@example.com',
        subject: 'Sujet',
        body: 'Bonjour',
      ).toString(),
      'mailto:client@example.com?subject=Sujet&body=Bonjour',
    );
  });
}
