class ServiceOffering {
  const ServiceOffering({
    required this.id,
    required this.title,
    required this.description,
    required this.unit,
    required this.isFeaturedHome,
    required this.imageUrl,
    required this.imageAlt,
    required this.durationLabel,
    required this.priceCents,
  });

  factory ServiceOffering.fromJson(Map<String, dynamic> json) {
    return ServiceOffering(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: (json['title'] as String?)?.trim() ?? '',
      description: (json['description'] as String?)?.trim(),
      unit: (json['unit'] as String?)?.trim(),
      isFeaturedHome: json['isFeaturedHome'] as bool? ?? false,
      imageUrl: (json['imageUrl'] as String?)?.trim() ?? '',
      imageAlt: (json['imageAlt'] as String?)?.trim(),
      durationLabel: (json['durationLabel'] as String?)?.trim(),
      priceCents: (json['priceCents'] as num?)?.toInt() ?? 0,
    );
  }

  final int id;
  final String title;
  final String? description;
  final String? unit;
  final bool isFeaturedHome;
  final String imageUrl;
  final String? imageAlt;
  final String? durationLabel;
  final int priceCents;
}
