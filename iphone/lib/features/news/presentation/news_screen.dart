import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hociatec_mobile/features/home/presentation/widgets/home_cards.dart';
import 'package:hociatec_mobile/features/news/data/news_repository.dart';

class NewsScreen extends ConsumerWidget {
  const NewsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final newsAsync = ref.watch(allNewsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Actualites')),
      body: newsAsync.when(
        data: (articles) => ListView.separated(
          padding: const EdgeInsets.all(20),
          itemBuilder: (context, index) => HomeNewsCard(article: articles[index]),
          separatorBuilder: (context, index) => const SizedBox(height: 14),
          itemCount: articles.length,
        ),
        error: (error, stackTrace) => Center(child: Text(error.toString())),
        loading: () => const Center(child: CircularProgressIndicator()),
      ),
    );
  }
}
