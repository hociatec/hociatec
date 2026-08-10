String productPublicUrl(String siteBaseUrl, String slug) {
  return Uri.parse(siteBaseUrl).resolve('/catalogue/produits/$slug').toString();
}

String newsPublicUrl(String siteBaseUrl, String slug) {
  return Uri.parse(siteBaseUrl).resolve('/actualites/$slug').toString();
}

Uri facebookShareUri(String absoluteUrl) {
  return Uri.parse(
    'https://www.facebook.com/sharer/sharer.php?u=${Uri.encodeComponent(absoluteUrl)}',
  );
}

Uri mailtoUri({
  required String email,
  required String subject,
  required String body,
}) {
  return Uri(
    scheme: 'mailto',
    path: email,
    queryParameters: <String, String>{
      'subject': subject,
      'body': body,
    },
  );
}
