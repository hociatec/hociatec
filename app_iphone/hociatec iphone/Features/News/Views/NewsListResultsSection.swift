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

                if viewModel.isLoading {
                    InlineLoadingStatus(message: "Actualisation des actualités…")
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
                FavoriteToggleButton(category: .news, targetId: article.id)

                Text(article.title)
                    .fontWeight(.semibold)
                    .multilineTextAlignment(.leading)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .accessibilityAddTraits(.isHeader)
            }

            Text(article.excerpt)
                .lineLimit(3)
                .foregroundStyle(.secondary)
            NavigationLink {
                NewsDetailView(api: service, slug: article.slug)
            } label: {
                Label("Lire l’actualité", systemImage: "arrow.right.circle")
                    .font(.footnote.weight(.semibold))
            }
            .buttonStyle(.borderless)
            .accessibilityHint("Ouvrir l’actualité")
        }
        .padding(.vertical, 4)
        .accessibilityElement(children: .contain)
    }
}
