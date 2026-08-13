import SwiftUI

struct NewsDetailLoadingSection: View {
    var body: some View {
        Section {
            ProgressView("Chargement de l’actualité...")
                .frame(maxWidth: .infinity, alignment: .center)
        }
    }
}

struct NewsDetailErrorSection: View {
    let error: String

    var body: some View {
        Section {
            Text(error)
                .foregroundStyle(.red)
        }
    }
}

struct NewsDetailHeroSection: View {
    let article: NewsArticle

    var body: some View {
        Section {
            VStack(alignment: .leading, spacing: 12) {
                HStack {
                    if let publishedAt = article.publishedAt {
                        Label(newsDateFormatter.string(from: publishedAt), systemImage: "calendar")
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                    }
                    if let category = article.category, !category.isEmpty {
                        Spacer()
                        Text(category)
                            .font(.caption)
                            .padding(.horizontal, 8)
                            .padding(.vertical, 4)
                            .background(Color(.secondarySystemBackground))
                            .clipShape(Capsule())
                    }
                }
                .accessibilityElement(children: .combine)
                .accessibilityLabel(metadataAccessibilityLabel)

                Text(article.title)
                    .font(.title2)
                    .fontWeight(.bold)
                    .accessibilityAddTraits(.isHeader)
                Text(article.excerpt)
                    .foregroundStyle(.secondary)
#if canImport(UIKit)
                ShareLink(
                    item: newsShareURL(for: article),
                    subject: Text(article.title),
                    message: Text(article.excerpt)
                ) {
                    Label("Partager l’actualité", systemImage: "square.and.arrow.up")
                        .fontWeight(.semibold)
                }
#endif
            }
            .accessibilityElement(children: .contain)
        }
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

struct NewsDetailContentSection: View {
    let content: String

    var body: some View {
        Section("Contenu") {
            Text(content)
                .textSelection(.enabled)
                .accessibilityElement(children: .combine)
        }
    }
}

struct NewsDetailCommentsSection: View {
    @ObservedObject var viewModel: NewsDetailViewModel
    let isLoggedIn: Bool

    var body: some View {
        Section("Commentaires") {
            NewsCommentsListSection(viewModel: viewModel)
            NewsCommentsPaginationSection(viewModel: viewModel)
            NewsCommentComposerSection(viewModel: viewModel, isLoggedIn: isLoggedIn)
        }
    }
}
