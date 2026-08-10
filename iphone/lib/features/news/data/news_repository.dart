import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/core/network/api_client.dart';
import 'package:hociatec_mobile/core/network/api_payload.dart';
import 'package:hociatec_mobile/features/news/domain/news_article.dart';

class NewsRepository {
  const NewsRepository(this._client);

  final ApiClient _client;

  Future<List<NewsArticle>> fetchLatest({int perPage = 3}) async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/news',
      queryParameters: <String, dynamic>{
        'page': 1,
        'perPage': perPage,
      },
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger les actualites.',
    );

    return readItemList(payload, 'Impossible de charger les actualites.')
        .map(NewsArticle.fromJson)
        .toList(growable: false);
  }

  Future<NewsArticle> fetchBySlug(String slug) async {
    final response = await _client.get<Map<String, dynamic>>(
      '/api/public/news/$slug',
    );

    final payload = unwrapApiDataMap(
      response.data,
      'Impossible de charger l actualite.',
    );

    final article = payload['article'];
    if (article is Map<String, dynamic>) {
      return NewsArticle.fromJson(article);
    }

    return NewsArticle.fromJson(payload);
  }
}

final newsRepositoryProvider = Provider<NewsRepository>((ref) {
  final client = ref.watch(apiClientProvider);
  return NewsRepository(client);
});

final latestNewsProvider = FutureProvider<List<NewsArticle>>((ref) {
  return ref.watch(newsRepositoryProvider).fetchLatest();
});

final newsDetailProvider = FutureProvider.family<NewsArticle, String>((ref, slug) {
  return ref.watch(newsRepositoryProvider).fetchBySlug(slug);
});
