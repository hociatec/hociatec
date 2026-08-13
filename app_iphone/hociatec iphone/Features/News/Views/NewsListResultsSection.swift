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
                    NavigationLink {
                        NewsDetailView(api: service, slug: article.slug)
                    } label: {
                        NewsListRow(article: article)
                    }
                }
            }
        }
    }
}

private struct NewsListRow: View {
    let article: NewsArticleSummary

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
            .accessibilityElement(children: .combine)
            .accessibilityLabel(metadataAccessibilityLabel)

            Text(article.title)
                .fontWeight(.semibold)
                .accessibilityAddTraits(.isHeader)
            Text(article.excerpt)
                .lineLimit(3)
                .foregroundStyle(.secondary)
        }
        .padding(.vertical, 4)
        .accessibilityElement(children: .contain)
    }

    private var metadataAccessibilityLabel: String {
        let parts = [
            article.publishedAt.map { "Publié le \(newsDateFormatter.string(from: $0))" },
            article.category.flatMap { category in
                category.isEmpty ? nil : "Catégorie \(category)"
            }
        ].compactMap { $0 }

        return parts.joined(separator: ". ")
    }
}
