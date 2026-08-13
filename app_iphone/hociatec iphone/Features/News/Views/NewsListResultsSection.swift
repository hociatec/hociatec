import SwiftUI

struct NewsListResultsSection: View {
    let service: NewsServing
    @ObservedObject var viewModel: NewsListViewModel

    var body: some View {
        Section {
            if viewModel.isLoading && viewModel.articles.isEmpty {
                ProgressView("Chargement des actualités...")
            } else if let error = viewModel.error {
                Text(error).foregroundStyle(.red)
            } else if viewModel.articles.isEmpty {
                Text("Aucune actualité disponible pour le moment.")
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

            Text(article.excerpt)
                .lineLimit(3)
                .foregroundStyle(.secondary)
        }
        .padding(.vertical, 4)
        .accessibilityElement(children: .contain)
    }
}
