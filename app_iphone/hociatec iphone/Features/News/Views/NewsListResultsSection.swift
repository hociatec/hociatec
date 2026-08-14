import SwiftUI

struct NewsListResultsSection: View {
    let service: NewsServing
    @ObservedObject var viewModel: NewsListViewModel

    var body: some View {
        Section {
            if viewModel.isLoading && viewModel.articles.isEmpty {
                ProgressView("Chargement des actualités...")
            } else if viewModel.articles.isEmpty {
                Text(
                    viewModel.appliedSearch.isEmpty
                        ? "Aucune actualité disponible pour le moment."
                        : "Aucune actualité ne correspond à cette recherche."
                )
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.articles) { article in
                    NewsListRow(service: service, article: article)
                }
            }
        }
    }
}

private struct NewsListRow: View {
    let service: NewsServing
    let article: NewsArticle

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                if let publishedAt = article.publishedAt {
                    Text(newsDateFormatter.string(from: publishedAt))
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
                if let category = article.category, !category.isEmpty {
                    Spacer()
                    Text(category)
                        .font(.caption)
                        .foregroundStyle(.secondary)
                }
            }
            .accessibilityHidden(true)

            HStack(alignment: .top, spacing: 12) {
                NavigationLink {
                    NewsDetailView(api: service, slug: article.slug)
                } label: {
                    Text(article.title)
                        .fontWeight(.semibold)
                        .multilineTextAlignment(.leading)
                        .frame(maxWidth: .infinity, alignment: .leading)
                }
                .buttonStyle(.plain)
                .accessibilityAddTraits(.isHeader)
                .accessibilityHint("Ouvrir l’actualité")

                FavoriteToggleButton(category: .news, targetId: article.id)
            }

            Text(article.excerpt)
                .lineLimit(3)
                .foregroundStyle(.secondary)
        }
        .padding(.vertical, 4)
        .accessibilityElement(children: .contain)
    }
}
