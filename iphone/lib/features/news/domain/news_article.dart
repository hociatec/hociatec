class NewsArticle {
  const NewsArticle({
    required this.id,
    required this.title,
    required this.slug,
    required this.excerpt,
    required this.content,
    required this.category,
    required this.publishedAt,
    required this.createdAt,
  });

  factory NewsArticle.fromJson(Map<String, dynamic> json) {
    return NewsArticle(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: (json['title'] as String?)?.trim() ?? '',
      slug: (json['slug'] as String?)?.trim() ?? '',
      excerpt: (json['excerpt'] as String?)?.trim() ?? '',
      content: (json['content'] as String?)?.trim() ?? '',
      category: (json['category'] as String?)?.trim(),
      publishedAt: (json['publishedAt'] as String?)?.trim(),
      createdAt: (json['createdAt'] as String?)?.trim() ?? '',
    );
  }

  final int id;
  final String title;
  final String slug;
  final String excerpt;
  final String content;
  final String? category;
  final String? publishedAt;
  final String createdAt;
}
