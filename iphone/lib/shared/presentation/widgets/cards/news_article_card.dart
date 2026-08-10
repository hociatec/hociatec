import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/news/domain/news_article.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class NewsArticleCard extends StatelessWidget {
  const NewsArticleCard({
    required this.article,
    super.key,
  });

  final NewsArticle article;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => context.push('/actualites/${article.slug}'),
      child: Ink(
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: <Color>[Color(0xFFFFFFFF), Color(0xFFFFF9F0)],
          ),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFDDD1C2)),
          boxShadow: const <BoxShadow>[
            BoxShadow(
              color: Color(0x142C1F10),
              blurRadius: 30,
              offset: Offset(0, 18),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: <Widget>[
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: <Widget>[
                  Text(
                    formatIsoDate(article.publishedAt ?? article.createdAt),
                    style: theme.textTheme.labelMedium?.copyWith(
                      color: const Color(0xFF73675B),
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  if ((article.category ?? '').isNotEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0x1FF39A20),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        article.category!.toUpperCase(),
                        style: theme.textTheme.labelSmall?.copyWith(
                          color: const Color(0xFF9D5624),
                          fontWeight: FontWeight.w900,
                          letterSpacing: 0.8,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                article.title,
                style: theme.textTheme.titleLarge?.copyWith(
                  color: const Color(0xFF171C24),
                  fontWeight: FontWeight.w900,
                  height: 1.24,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                article.excerpt,
                maxLines: 4,
                overflow: TextOverflow.ellipsis,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: const Color(0xFF61574F),
                  height: 1.65,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
