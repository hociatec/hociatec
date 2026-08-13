import SwiftUI

struct HomeNewsSection: View {
    @EnvironmentObject private var container: AppContainer
    @ObservedObject var home: HomeViewModel

    var body: some View {
        Section("Actualités") {
            if home.isLoading && home.news.isEmpty {
                ProgressView("Chargement...")
                    .frame(maxWidth: .infinity, alignment: .center)
            } else if let error = home.error, home.news.isEmpty {
                Text(error)
                    .foregroundStyle(.red)
            } else if home.news.isEmpty {
                Text("Aucune actualité disponible pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(home.news) { article in
                    NavigationLink {
                        NewsDetailView(api: container.services.news, slug: article.slug)
                    } label: {
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
                            Text(article.title)
                                .fontWeight(.semibold)
                            Text(article.excerpt)
                                .lineLimit(3)
                                .foregroundStyle(.secondary)
                        }
                        .padding(.vertical, 4)
                    }
                }
            }
        }
    }
}
