import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/cards/app_cards.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/status_message_card.dart';
import 'package:hociatec_mobile/shared/utils/api_error_message.dart';

class NewsScreen extends ConsumerWidget {
  const NewsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final newsAsync = ref.watch(allNewsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Actualites')),
      body: newsAsync.when(
        data: (articles) {
          if (articles.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: StatusMessageCard(
                  message: 'Aucune actualite disponible pour le moment.',
                ),
              ),
            );
          }

          return ListView.separated(
            padding: const EdgeInsets.all(20),
            itemBuilder: (context, index) =>
                NewsArticleCard(article: articles[index]),
            separatorBuilder: (context, index) => const SizedBox(height: 14),
            itemCount: articles.length,
          );
        },
        error: (error, stackTrace) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: StatusMessageCard(
              message: resolveApiErrorMessage(
                error,
                'Impossible de charger les actualites.',
              ),
            ),
          ),
        ),
        loading: () => const Center(child: CircularProgressIndicator()),
      ),
    );
  }
}
